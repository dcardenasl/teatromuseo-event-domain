<?php

declare(strict_types=1);

namespace App\Traits\Services;

use App\Libraries\Localization\PublicSlugStore;
use App\Libraries\Localization\TranslationFieldCatalog;

/**
 * Public routing slug lifecycle for a CRUD service that already uses
 * HasLocalizedTranslations.
 *
 * The consuming service composes both traits explicitly in its own
 * beforeStore/afterStore/beforeUpdate/afterUpdate/enrichEntities/mapToResponse
 * hooks: extract manual slugs before the translation store validates the
 * payload, sync slugs after translations were persisted, and attach
 * slug/slugs when mapping responses.
 */
trait HasPublicSlugs
{
    protected PublicSlugStore $slugStore;
    protected string $slugResourceType;

    /** Localized field the slug is generated from ('title', 'name', ...). */
    protected string $slugSourceField;

    /** @var array<string, string> Manual slugs pulled off the translations payload, locale => slug. */
    private array $pendingManualSlugs = [];

    /**
     * Pull editor-provided slugs out of the translations payload before the
     * translation store validates it (slug is a routing field, not content).
     *
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    protected function extractManualSlugs(array &$data): array
    {
        if (! is_array($data['translations'] ?? null)) {
            return [];
        }

        $manualSlugs = [];
        foreach ($data['translations'] as $key => $row) {
            if (! is_array($row)) {
                continue;
            }

            $locale = (string) ($row['locale'] ?? $row['language_code'] ?? (is_string($key) ? $key : ''));
            $slug = $row['slug'] ?? null;
            unset($row['slug']);
            $data['translations'][$key] = $row;

            if ($locale !== '' && is_string($slug) && trim($slug) !== '') {
                $manualSlugs[strtolower(str_replace('_', '-', trim($locale)))] = trim($slug);
            }
        }

        return $manualSlugs;
    }

    /**
     * Ensure every locale with a localized source value carries a slug.
     * Existing slugs are stable; manual slugs from the last payload win.
     */
    protected function syncPublicSlugs(object $entity): void
    {
        $id = (int) ($entity->id ?? 0);
        if ($id < 1) {
            return;
        }

        $sourceByLocale = [];
        foreach ($this->translationStore->forResource($this->slugResourceType, $id) as $row) {
            $value = trim((string) ($row['fields'][$this->slugSourceField] ?? ''));
            if ($value !== '') {
                $sourceByLocale[$row['locale']] = $value;
            }
        }

        $legacyLocale = TranslationFieldCatalog::fallbackLocale();
        $legacyValue = trim((string) ($entity->{$this->slugSourceField} ?? ''));
        if (! isset($sourceByLocale[$legacyLocale]) && $legacyValue !== '') {
            $sourceByLocale[$legacyLocale] = $legacyValue;
        }

        $this->slugStore->syncForResource($this->slugResourceType, $id, $sourceByLocale, $this->pendingManualSlugs);
        $this->pendingManualSlugs = [];
    }

    /**
     * Batch-attach slug/slugs to a page of entities (no per-row queries).
     *
     * @param array<int, object> $entities
     * @return array<int, object>
     */
    protected function attachSlugs(array $entities): array
    {
        $ids = array_values(array_filter(array_map(
            static fn (object $entity): int => (int) ($entity->id ?? 0),
            $entities,
        ), static fn (int $id): bool => $id > 0));
        $slugs = $this->slugStore->slugsForResources($this->slugResourceType, $ids);

        foreach ($entities as $entity) {
            $entitySlugs = $slugs[(int) ($entity->id ?? 0)] ?? [];
            $entity->slugs = $entitySlugs;
            $entity->slug = $this->slugStore->resolveSlug($entitySlugs);
        }

        return $entities;
    }

    /**
     * Attach slug/slugs to a single entity when it skipped enrichEntities
     * (the store/update response path).
     */
    protected function attachSlugsToEntity(object $entity): void
    {
        if (is_array($entity->slugs ?? null)) {
            return;
        }

        $entitySlugs = $this->slugStore->slugsForResource($this->slugResourceType, (int) ($entity->id ?? 0));
        $entity->slugs = $entitySlugs;
        $entity->slug = $this->slugStore->resolveSlug($entitySlugs);
    }
}
