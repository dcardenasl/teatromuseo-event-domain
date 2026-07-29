<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Entities\EventEntity;
use App\Interfaces\Events\EventServiceInterface;
use App\Libraries\Localization\LocalizedTranslationStore;
use App\Libraries\Localization\PublicSlugStore;
use App\Traits\Services\HasLocalizedTranslations;
use App\Traits\Services\HasPublicSlugs;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Exceptions\NotFoundException;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

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

    /**
     * @param RepositoryInterface<EventEntity> $eventRepository
     */
    public function __construct(
        RepositoryInterface $eventRepository,
        ResponseMapperInterface $responseMapper,
        LocalizedTranslationStore $translationStore,
        PublicSlugStore $slugStore,
    ) {
        parent::__construct($eventRepository, $responseMapper);
        $this->translationStore = $translationStore;
        $this->localizedResourceType = 'event';
        $this->slugStore = $slugStore;
        $this->slugResourceType = 'event';
        $this->slugSourceField = 'title';
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
        return $this->attachSlugs($this->localizedEnrichEntities($entities));
    }

    protected function mapToResponse(object $entity): DataTransferObjectInterface
    {
        $this->attachSlugsToEntity($entity);

        return $this->localizedMapToResponse($entity);
    }
}
