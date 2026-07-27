<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Entities\OccurrenceEntity;
use App\Interfaces\Events\OccurrenceServiceInterface;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<OccurrenceEntity>
 */
class OccurrenceService extends BaseCrudService implements OccurrenceServiceInterface
{
    /**
     * @param RepositoryInterface<OccurrenceEntity> $occurrenceRepository
     */
    public function __construct(
        RepositoryInterface $occurrenceRepository,
        ResponseMapperInterface $responseMapper
    ) {
        parent::__construct($occurrenceRepository, $responseMapper);
    }

    /**
     * Domain Hooks
     *
     * Implement beforeStore, afterStore, beforeUpdate, etc.,
     * to add specific business logic while keeping the service layer clean.
     */

    // Custom methods declared in OccurrenceServiceInterface must be implemented here.
    // Until fully implemented, throw to avoid silent incorrect behavior:
    //   throw new \BadMethodCallException(__METHOD__ . ' not implemented');
}
