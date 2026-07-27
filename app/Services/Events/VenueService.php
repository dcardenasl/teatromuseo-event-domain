<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Entities\VenueEntity;
use App\Interfaces\Events\VenueServiceInterface;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<VenueEntity>
 */
class VenueService extends BaseCrudService implements VenueServiceInterface
{
    /**
     * @param RepositoryInterface<VenueEntity> $venueRepository
     */
    public function __construct(
        RepositoryInterface $venueRepository,
        ResponseMapperInterface $responseMapper
    ) {
        parent::__construct($venueRepository, $responseMapper);
    }

    /**
     * Domain Hooks
     *
     * Implement beforeStore, afterStore, beforeUpdate, etc.,
     * to add specific business logic while keeping the service layer clean.
     */

    // Custom methods declared in VenueServiceInterface must be implemented here.
    // Until fully implemented, throw to avoid silent incorrect behavior:
    //   throw new \BadMethodCallException(__METHOD__ . ' not implemented');
}
