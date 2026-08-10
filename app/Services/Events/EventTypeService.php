<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Entities\EventTypeEntity;
use App\Interfaces\Events\EventTypeServiceInterface;
use App\Interfaces\PublicCacheInvalidationNotifierInterface;
use App\Models\EventPublicSlugModel;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Localization\LocalizedTranslationStore;
use dcardenasl\Ci4ApiCore\Localization\PublicSlugStore;
use dcardenasl\Ci4ApiCore\Localization\SlugGenerator;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;
use dcardenasl\Ci4ApiCore\Services\HasLocalizedTranslations;
use dcardenasl\Ci4ApiCore\Services\HasPublicSlugs;

/**
 * @extends BaseCrudService<EventTypeEntity>
 */
class EventTypeService extends BaseCrudService implements EventTypeServiceInterface
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
     * @param RepositoryInterface<EventTypeEntity> $eventTypeRepository
     */
    public function __construct(
        RepositoryInterface $eventTypeRepository,
        ResponseMapperInterface $responseMapper,
        LocalizedTranslationStore $translationStore,
        PublicSlugStore $slugStore,
        private EventPublicSlugModel $publicSlugModel,
        private readonly PublicCacheInvalidationNotifierInterface $cacheInvalidator,
    ) {
        parent::__construct($eventTypeRepository, $responseMapper);
        $this->translationStore = $translationStore;
        $this->localizedResourceType = 'event_type';
        $this->slugStore = $slugStore;
        $this->slugResourceType = 'event_type';
        $this->slugSourceField = 'name';
    }

    public function isSlugAvailable(string $slug, string $locale, int $currentId = 0): bool
    {
        $slug = (new SlugGenerator())->slugify($slug);
        $locale = strtolower(trim($locale));
        if ($slug === '' || $locale === '') {
            return false;
        }

        $query = $this->publicSlugModel
            ->where('resource_type', 'event_type')
            ->where('locale', $locale)
            ->where('slug', $slug);
        if ($currentId > 0) {
            $query->where('resource_id !=', $currentId);
        }

        return $query->countAllResults() === 0;
    }

    /** @param array<string, mixed> $data */
    protected function beforeStore(array $data, ?SecurityContext $context): array
    {
        $this->pendingManualSlugs = $this->extractManualSlugs($data);

        return $this->localizedBeforeStore($data, $context);
    }

    protected function afterStore(object $entity, ?SecurityContext $context): void
    {
        $this->localizedAfterStore($entity, $context);
        $this->syncPublicSlugs($entity);
        $this->cacheInvalidator->invalidate(['event_types', 'events']);
    }

    /** @param array<string, mixed> $data */
    protected function beforeUpdate(int $id, array $data, ?SecurityContext $context): array
    {
        $this->pendingManualSlugs = $this->extractManualSlugs($data);

        return $this->localizedBeforeUpdate($id, $data, $context);
    }

    protected function afterUpdate(object $entity, ?SecurityContext $context): void
    {
        $this->localizedAfterUpdate($entity, $context);
        $this->syncPublicSlugs($entity);
        $this->cacheInvalidator->invalidate(['event_types', 'events']);
    }

    protected function afterDelete(object $entity, ?SecurityContext $context): void
    {
        parent::afterDelete($entity, $context);
        $this->cacheInvalidator->invalidate(['event_types', 'events']);
    }

    /** @param array<int, object> $entities */
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
