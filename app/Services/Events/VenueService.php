<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Entities\VenueEntity;
use App\Interfaces\Events\VenueServiceInterface;
use App\Interfaces\PublicCacheInvalidationNotifierInterface;
use dcardenasl\Ci4ApiCore\Localization\LocalizedTranslationStore;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;
use dcardenasl\Ci4ApiCore\Services\HasLocalizedTranslations;

/**
 * @extends BaseCrudService<VenueEntity>
 */
class VenueService extends BaseCrudService implements VenueServiceInterface
{
    use HasLocalizedTranslations;

    /**
     * @param RepositoryInterface<VenueEntity> $venueRepository
     */
    public function __construct(
        RepositoryInterface $venueRepository,
        ResponseMapperInterface $responseMapper,
        LocalizedTranslationStore $translationStore,
        private readonly PublicCacheInvalidationNotifierInterface $cacheInvalidator,
    ) {
        parent::__construct($venueRepository, $responseMapper);
        $this->translationStore = $translationStore;
        $this->localizedResourceType = 'venue';
    }

    protected function afterStore(object $entity, ?\dcardenasl\Ci4ApiCore\Dto\SecurityContext $context): void
    {
        parent::afterStore($entity, $context);
        $this->cacheInvalidator->invalidate(['events']);
    }

    protected function afterUpdate(object $entity, ?\dcardenasl\Ci4ApiCore\Dto\SecurityContext $context): void
    {
        parent::afterUpdate($entity, $context);
        $this->cacheInvalidator->invalidate(['events']);
    }

    protected function afterDelete(object $entity, ?\dcardenasl\Ci4ApiCore\Dto\SecurityContext $context): void
    {
        parent::afterDelete($entity, $context);
        $this->cacheInvalidator->invalidate(['events']);
    }
}
