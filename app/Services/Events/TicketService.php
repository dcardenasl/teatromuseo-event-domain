<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Entities\TicketEntity;
use App\Interfaces\Events\TicketServiceInterface;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<TicketEntity>
 */
class TicketService extends BaseCrudService implements TicketServiceInterface
{
    /**
     * @param RepositoryInterface<TicketEntity> $ticketRepository
     */
    public function __construct(
        RepositoryInterface $ticketRepository,
        ResponseMapperInterface $responseMapper
    ) {
        parent::__construct($ticketRepository, $responseMapper);
    }

    /**
     * Domain Hooks
     *
     * Implement beforeStore, afterStore, beforeUpdate, etc.,
     * to add specific business logic while keeping the service layer clean.
     */

    // Custom methods declared in TicketServiceInterface must be implemented here.
    // Until fully implemented, throw to avoid silent incorrect behavior:
    //   throw new \BadMethodCallException(__METHOD__ . ' not implemented');
}
