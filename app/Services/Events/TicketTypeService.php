<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Entities\TicketTypeEntity;
use App\Interfaces\Events\TicketTypeServiceInterface;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<TicketTypeEntity>
 */
class TicketTypeService extends BaseCrudService implements TicketTypeServiceInterface
{
    /**
     * @param RepositoryInterface<TicketTypeEntity> $ticketTypeRepository
     */
    public function __construct(
        RepositoryInterface $ticketTypeRepository,
        ResponseMapperInterface $responseMapper
    ) {
        parent::__construct($ticketTypeRepository, $responseMapper);
    }

    /**
     * Domain Hooks
     *
     * Implement beforeStore, afterStore, beforeUpdate, etc.,
     * to add specific business logic while keeping the service layer clean.
     */

    // Custom methods declared in TicketTypeServiceInterface must be implemented here.
    // Until fully implemented, throw to avoid silent incorrect behavior:
    //   throw new \BadMethodCallException(__METHOD__ . ' not implemented');
}
