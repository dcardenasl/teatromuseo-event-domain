<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Entities\TicketTypeEntity;
use App\Interfaces\Events\TicketTypeServiceInterface;
use App\Libraries\Localization\LocalizedTranslationStore;
use App\Traits\Services\HasLocalizedTranslations;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<TicketTypeEntity>
 */
class TicketTypeService extends BaseCrudService implements TicketTypeServiceInterface
{
    use HasLocalizedTranslations;

    /**
     * @param RepositoryInterface<TicketTypeEntity> $ticketTypeRepository
     */
    public function __construct(
        RepositoryInterface $ticketTypeRepository,
        ResponseMapperInterface $responseMapper,
        LocalizedTranslationStore $translationStore,
    ) {
        parent::__construct($ticketTypeRepository, $responseMapper);
        $this->translationStore = $translationStore;
        $this->localizedResourceType = 'ticket_type';
    }
}
