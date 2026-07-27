<?php

declare(strict_types=1);

namespace App\Traits\Services;

use App\Libraries\Localization\LocalizedTranslationStore;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;

/**
 * Adds the common localized-content lifecycle to a CRUD service.
 *
 * The parent CRUD transaction remains the transaction boundary. Translation
 * rows are synchronized from afterStore/afterUpdate, so a failed translation
 * write rolls back the main resource and the localized content together.
 */
trait HasLocalizedTranslations
{
    protected LocalizedTranslationStore $translationStore;
    protected string $localizedResourceType;

    /** @var list<array<int|string, mixed>>|null */
    private ?array $pendingLocalizedTranslations = null;
    private bool $localizedTranslationsProvided = false;
    private bool $localizedProjectionDirty = false;

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function beforeStore(array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeStore($data, $context);
        $this->localizedTranslationsProvided = array_key_exists('translations', $data);
        $this->pendingLocalizedTranslations = $this->localizedTranslationsProvided && is_array($data['translations'])
            ? array_values($data['translations'])
            : [];
        unset($data['translations']);

        return $data;
    }

    protected function afterStore(object $entity, ?SecurityContext $context): void
    {
        parent::afterStore($entity, $context);

        $id = (int) ($entity->id ?? 0);
        if ($id < 1) {
            return;
        }

        $rows = $this->pendingLocalizedTranslations ?? [];
        if ($rows === []) {
            $this->translationStore->syncLegacyProjection($this->localizedResourceType, $id, $entity->toArray());
            return;
        }

        $this->translationStore->sync($this->localizedResourceType, $id, $rows);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function beforeUpdate(int $id, array $data, ?SecurityContext $context): array
    {
        $data = parent::beforeUpdate($id, $data, $context);
        $this->localizedTranslationsProvided = array_key_exists('translations', $data);
        $this->pendingLocalizedTranslations = $this->localizedTranslationsProvided && is_array($data['translations'])
            ? array_values($data['translations'])
            : [];
        $this->localizedProjectionDirty = array_intersect(
            array_keys($data),
            $this->translationStore->fields($this->localizedResourceType),
        ) !== [];

        unset($data['translations']);

        // BaseCrudService rejects an empty update before afterUpdate() runs.
        // A translation-only update is valid, so keep the main update legal by
        // writing the current value of the first legacy projection field.
        if ($this->localizedTranslationsProvided && $data === []) {
            $current = $this->repository->find($id);
            $projectionField = $this->translationStore->fields($this->localizedResourceType)[0] ?? null;
            if ($current !== null && $projectionField !== null) {
                $data[$projectionField] = $current->{$projectionField};
            }
        }
        return $data;
    }

    protected function afterUpdate(object $entity, ?SecurityContext $context): void
    {
        parent::afterUpdate($entity, $context);

        $id = (int) ($entity->id ?? 0);
        if ($id < 1) {
            return;
        }

        if ($this->localizedTranslationsProvided) {
            if (($this->pendingLocalizedTranslations ?? []) === []) {
                $this->translationStore->clear($this->localizedResourceType, $id);
                return;
            }

            $this->translationStore->sync(
                $this->localizedResourceType,
                $id,
                $this->pendingLocalizedTranslations ?? [],
            );
            return;
        }

        if ($this->localizedProjectionDirty) {
            $this->translationStore->syncLegacyProjection($this->localizedResourceType, $id, $entity->toArray());
        }
    }

    /**
     * @param array<int, object> $entities
     * @return array<int, object>
     */
    protected function enrichEntities(array $entities): array
    {
        $entities = parent::enrichEntities($entities);
        $ids = array_values(array_filter(array_map(
            static fn (object $entity): int => (int) ($entity->id ?? 0),
            $entities,
        ), static fn (int $id): bool => $id > 0));
        $translations = $this->translationStore->forResources($this->localizedResourceType, $ids);

        foreach ($entities as $entity) {
            $id = (int) ($entity->id ?? 0);
            $rows = $translations[$id] ?? [];
            if ($rows === []) {
                $this->translationStore->appendLegacyRow($this->localizedResourceType, $rows, $entity->toArray());
            }

            $entity->translations = $rows;
            $entity->localized = $this->translationStore->resolve(
                $this->localizedResourceType,
                $rows,
                $entity->toArray(),
            );
        }

        return $entities;
    }

    protected function mapToResponse(object $entity): DataTransferObjectInterface
    {
        $payload = $entity->toArray();
        $internalTranslations = is_array($payload['translations'] ?? null)
            ? $payload['translations']
            : $this->translationStore->forResource($this->localizedResourceType, (int) ($payload['id'] ?? 0));

        if ($internalTranslations === []) {
            $this->translationStore->appendLegacyRow($this->localizedResourceType, $internalTranslations, $payload);
        }

        $payload['localized'] = is_array($payload['localized'] ?? null)
            ? $payload['localized']
            : $this->translationStore->resolve($this->localizedResourceType, $internalTranslations, $payload);
        $payload['translations'] = $this->translationStore->toPayloadRows($internalTranslations);

        return $this->responseMapper->map($payload);
    }
}
