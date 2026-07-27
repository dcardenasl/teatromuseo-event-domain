<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Entities\EventReferenceEntity;
use App\Interfaces\Events\EventReferenceServiceInterface;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<EventReferenceEntity>
 */
class EventReferenceService extends BaseCrudService implements EventReferenceServiceInterface
{
    /**
     * @param RepositoryInterface<EventReferenceEntity> $eventReferenceRepository
     */
    public function __construct(
        RepositoryInterface $eventReferenceRepository,
        ResponseMapperInterface $responseMapper
    ) {
        parent::__construct($eventReferenceRepository, $responseMapper);
    }

    /**
     * Domain Hooks
     *
     * Implement beforeStore, afterStore, beforeUpdate, etc.,
     * to add specific business logic while keeping the service layer clean.
     */

    // Custom methods declared in EventReferenceServiceInterface must be implemented here.
    // Until fully implemented, throw to avoid silent incorrect behavior:
    //   throw new \BadMethodCallException(__METHOD__ . ' not implemented');
}
