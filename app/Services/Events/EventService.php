<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Entities\EventEntity;
use App\Interfaces\Events\EventServiceInterface;
use App\Interfaces\Events\OccurrenceRepositoryInterface;
use DateTimeImmutable;
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
        string $scheduleTimezone = 'America/Santiago',
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

    /**
     * A "future ascending, then past descending" order can't be expressed by the single
     * ORDER BY direction the generic `sort` criteria produces, and re-sorting only within an
     * already-paginated page would miss most upcoming events (they're a small slice of the
     * whole chronological table). So this walks every matching row through the normal
     * paginated criteria path (bounded by this domain's real event volume — a single venue's
     * programming, not a high-volume table), re-sorts in PHP, then paginates that result.
     */
    public function indexPublicCartelera(DataTransferObjectInterface $request, ?SecurityContext $context = null): DataTransferObjectInterface
    {
        $requestData = $request->toArray();
        $page = max(1, (int) ($requestData['page'] ?? 1));
        $perPage = max(1, (int) ($requestData['per_page'] ?? 20));
        $requestData['filter'] = is_array($requestData['filter'] ?? null) ? $requestData['filter'] : [];
        $requestData['filter']['status'] = 'published';

        $criteria = $this->applyQueryOptions($requestData);
        $requestedSort = trim((string) ($requestData['sort'] ?? ''));
        unset($criteria['sort']);
        $baseCriteria = function ($builder): void {
            $this->applyBaseCriteria($builder);
        };

        // An explicit editor/public-listing sort is authoritative. The
        // special chronological ordering remains the default only when no
        // sort was requested, preserving the existing cartelera behavior.
        if ($requestedSort !== '') {
            $allEntities = [];
            $walkPage = 1;
            do {
                $result = $this->repository->paginateCriteria(
                    [...$criteria, 'sort' => $requestedSort],
                    $walkPage,
                    100,
                    $baseCriteria,
                );
                $allEntities = array_merge($allEntities, (array) $result['data']);
                $walkPage++;
            } while ($walkPage <= (int) $result['last_page']);

            $entities = array_values(array_filter(
                $this->enrichEntities($allEntities),
                fn (object $entity): bool => $this->hasOccurrences($entity)
            ));
            $pageEntities = array_slice($entities, ($page - 1) * $perPage, $perPage);
            $data = array_map(fn (object $entity): DataTransferObjectInterface => $this->mapToResponse($entity), $pageEntities);

            return PaginatedResponseDTO::fromArray([
                'data' => $data,
                'total' => count($entities),
                'page' => $page,
                'per_page' => $perPage,
            ]);
        }

        $allEntities = [];
        $walkPage = 1;
        do {
            $result = $this->repository->paginateCriteria($criteria, $walkPage, 100, $baseCriteria);
            $allEntities = array_merge($allEntities, (array) $result['data']);
            $walkPage++;
        } while ($walkPage <= (int) $result['last_page']);

        $allEntities = array_values(array_filter(
            $this->enrichEntities($allEntities),
            fn (object $entity): bool => $this->hasOccurrences($entity)
        ));
        $now = (new DateTimeImmutable('now', $this->scheduleTimezone))->format('Y-m-d H:i:s');
        usort($allEntities, static function (object $a, object $b) use ($now): int {
            $aStart = self::firstOccurrenceStart($a);
            $bStart = self::firstOccurrenceStart($b);
            if ($aStart === '' && $bStart === '') {
                return 0;
            }
            if ($aStart === '') {
                return 1;
            }
            if ($bStart === '') {
                return -1;
            }
            $aFuture = $aStart >= $now;
            $bFuture = $bStart >= $now;
            if ($aFuture !== $bFuture) {
                return $aFuture ? -1 : 1;
            }

            return $aFuture ? $aStart <=> $bStart : $bStart <=> $aStart;
        });

        $total = count($allEntities);
        $pageEntities = array_slice($allEntities, ($page - 1) * $perPage, $perPage);

        $data = array_map(fn (object $entity): DataTransferObjectInterface => $this->mapToResponse($entity), $pageEntities);

        return PaginatedResponseDTO::fromArray([
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ]);
    }

    private function hasOccurrences(object $entity): bool
    {
        $occurrences = $entity->occurrences ?? [];

        return is_array($occurrences) && $occurrences !== [];
    }

    private static function firstOccurrenceStart(object $entity): string
    {
        $occurrences = $entity->occurrences ?? [];
        if (! is_array($occurrences) || $occurrences === []) {
            return '';
        }

        $first = $occurrences[0] ?? [];

        return is_array($first) ? (string) ($first['start_time'] ?? '') : '';
    }
}
