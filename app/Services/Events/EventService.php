<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\DTO\Response\Events\EventResponseDTO;
use App\Entities\EventEntity;
use App\Interfaces\Events\AdminListProjectionRepositoryInterface;
use App\Interfaces\Events\EventServiceInterface;
use App\Interfaces\Events\OccurrenceRepositoryInterface;
use App\Interfaces\PublicCacheInvalidationNotifierInterface;
use App\Support\AdminListProjectionDecoder;
use DateTimeZone;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Dto\PaginatedResponseDTO;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;
use dcardenasl\Ci4ApiCore\Localization\LocalizedTranslationStore;
use dcardenasl\Ci4ApiCore\Localization\PublicSlugStore;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;
use dcardenasl\Ci4ApiCore\Services\HasLocalizedTranslations;
use dcardenasl\Ci4ApiCore\Services\HasPublicSlugs;

/**
 * @extends BaseCrudService<EventEntity>
 */
class EventService extends BaseCrudService implements EventServiceInterface
{
    use HasLocalizedTranslations {
        beforeStore as private localizedBeforeStore;
        afterStore as private localizedAfterStore;
        beforeUpdate as private localizedBeforeUpdate;
        afterUpdate as private localizedAfterUpdate;
        enrichEntities as private localizedEnrichEntities;
        mapToResponse as private localizedMapToResponse;
    }
    use HasPublicSlugs;

    private OccurrenceRepositoryInterface $occurrenceRepository;
    private DateTimeZone $scheduleTimezone;

    /**
     * @param RepositoryInterface<EventEntity> $eventRepository
     */
    public function __construct(
        RepositoryInterface $eventRepository,
        ResponseMapperInterface $responseMapper,
        LocalizedTranslationStore $translationStore,
        PublicSlugStore $slugStore,
        OccurrenceRepositoryInterface $occurrenceRepository,
        string $scheduleTimezone,
        private readonly PublicCacheInvalidationNotifierInterface $cacheInvalidator,
        private readonly ?AdminListProjectionRepositoryInterface $eventListRepository = null,
    ) {
        parent::__construct($eventRepository, $responseMapper);
        $this->translationStore = $translationStore;
        $this->localizedResourceType = 'event';
        $this->slugStore = $slugStore;
        $this->slugResourceType = 'event';
        $this->slugSourceField = 'title';
        $this->occurrenceRepository = $occurrenceRepository;
        $this->scheduleTimezone = new DateTimeZone($scheduleTimezone);
    }

    public function index(DataTransferObjectInterface $request, ?SecurityContext $context = null): DataTransferObjectInterface
    {
        $requestData = $request->toArray();
        if (($requestData['projection'] ?? 'full') !== 'list' || $this->eventListRepository === null) {
            return parent::index($request, $context);
        }

        $result = $this->eventListRepository->paginateAdminList($requestData, (int) ($requestData['page'] ?? 1), (int) ($requestData['per_page'] ?? 20));
        $data = array_map(static function (array $row): EventResponseDTO {
            $decoded = AdminListProjectionDecoder::translations($row['translations_data'] ?? null);
            $row['translations'] = array_map(static fn (array $translation): array => [
                'locale' => $translation['locale'],
                ...$translation['fields'],
            ], $decoded);
            $row['slugs'] = AdminListProjectionDecoder::slugs($row['slugs_data'] ?? null);
            $row['slug'] = $row['slugs'] !== [] ? (string) reset($row['slugs']) : '';
            $row['localized'] = array_filter([
                'title' => (string) ($row['title'] ?? ''),
                'description' => (string) ($row['description'] ?? ''),
            ], static fn (string $value): bool => $value !== '');
            $row['occurrences'] = [];
            unset($row['translations_data'], $row['slugs_data'], $row['total_items']);

            return EventResponseDTO::fromArray($row);
        }, $result['data']);

        return PaginatedResponseDTO::fromArray(['data' => $data, 'total' => $result['total'], 'page' => $result['page'], 'per_page' => $result['per_page']]);
    }

    /**
     * Public detail lookup: numeric id, uuid, or a per-locale routing slug.
     * Only published events are visible through this path.
     *
     * @return array<string, mixed>
     */
    public function getPublicByIdOrSlug(string $idOrSlug): array
    {
        $idOrSlug = trim($idOrSlug);
        $entity = null;

        if (ctype_digit($idOrSlug)) {
            $entity = $this->repository->find((int) $idOrSlug);
        } elseif (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $idOrSlug) === 1) {
            $entity = $this->repository->findBy('uuid', $idOrSlug);
        } else {
            $eventId = $this->slugStore->resolveResourceId('event', $idOrSlug);
            if ($eventId !== null) {
                $entity = $this->repository->find($eventId);
            }
        }

        if (! $entity instanceof EventEntity || (string) $entity->status !== 'published') {
            throw new NotFoundException(lang('Events.not_found'));
        }

        $enriched = $this->enrichEntities([$entity]);

        if (! $this->hasOccurrences($enriched[0] ?? $entity)) {
            throw new NotFoundException(lang('Events.not_found'));
        }

        return $this->mapToResponse($enriched[0] ?? $entity)->toArray();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function beforeStore(array $data, ?SecurityContext $context): array
    {
        $this->pendingManualSlugs = $this->extractManualSlugs($data);

        return $this->localizedBeforeStore($data, $context);
    }

    protected function afterStore(object $entity, ?SecurityContext $context): void
    {
        $this->localizedAfterStore($entity, $context);
        $this->syncPublicSlugs($entity);
        $this->cacheInvalidator->invalidate(['events']);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function beforeUpdate(int $id, array $data, ?SecurityContext $context): array
    {
        $this->pendingManualSlugs = $this->extractManualSlugs($data);

        return $this->localizedBeforeUpdate($id, $data, $context);
    }

    protected function afterUpdate(object $entity, ?SecurityContext $context): void
    {
        $this->localizedAfterUpdate($entity, $context);
        $this->syncPublicSlugs($entity);
        $this->cacheInvalidator->invalidate(['events']);
    }

    protected function afterDelete(object $entity, ?SecurityContext $context): void
    {
        parent::afterDelete($entity, $context);
        $this->cacheInvalidator->invalidate(['events']);
    }

    /**
     * @param array<int, object> $entities
     * @return array<int, object>
     */
    protected function enrichEntities(array $entities): array
    {
        $entities = $this->attachSlugs($this->localizedEnrichEntities($entities));
        $eventIds = [];
        foreach ($entities as $entity) {
            if ($entity instanceof EventEntity && isset($entity->id)) {
                $eventIds[] = (int) $entity->id;
            }
        }

        $occurrencesByEvent = [];
        foreach ($this->occurrenceRepository->findByEventIds(array_values(array_unique($eventIds))) as $occurrence) {
            $occurrencesByEvent[(int) $occurrence->event_id][] = array_merge(
                $occurrence->toArray(),
                ['timezone' => $this->scheduleTimezone->getName()]
            );
        }

        foreach ($entities as $entity) {
            if ($entity instanceof EventEntity) {
                $entity->occurrences = $occurrencesByEvent[(int) $entity->id] ?? [];
            }
        }

        return $entities;
    }

    protected function mapToResponse(object $entity): DataTransferObjectInterface
    {
        $this->attachSlugsToEntity($entity);

        return $this->localizedMapToResponse($entity);
    }

    private function hasOccurrences(object $entity): bool
    {
        $occurrences = $entity->occurrences ?? [];

        return is_array($occurrences) && $occurrences !== [];
    }
}
