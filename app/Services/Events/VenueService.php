<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Entities\VenueEntity;
use App\Interfaces\Events\VenueServiceInterface;
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
    ) {
        parent::__construct($venueRepository, $responseMapper);
        $this->translationStore = $translationStore;
        $this->localizedResourceType = 'venue';
    }
}
