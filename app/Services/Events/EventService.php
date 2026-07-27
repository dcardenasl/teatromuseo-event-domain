<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Entities\EventEntity;
use App\Interfaces\Events\EventServiceInterface;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<EventEntity>
 */
class EventService extends BaseCrudService implements EventServiceInterface
{
    /**
     * @param RepositoryInterface<EventEntity> $eventRepository
     */
    public function __construct(
        RepositoryInterface $eventRepository,
        ResponseMapperInterface $responseMapper
    ) {
        parent::__construct($eventRepository, $responseMapper);
    }

    /**
     * Domain Hooks
     *
     * Implement beforeStore, afterStore, beforeUpdate, etc.,
     * to add specific business logic while keeping the service layer clean.
     */

    // Custom methods declared in EventServiceInterface must be implemented here.
    // Until fully implemented, throw to avoid silent incorrect behavior:
    //   throw new \BadMethodCallException(__METHOD__ . ' not implemented');
}
