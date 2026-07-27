<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Entities\EventEntity;
use App\Interfaces\Events\EventServiceInterface;
use App\Libraries\Localization\LocalizedTranslationStore;
use App\Traits\Services\HasLocalizedTranslations;
use dcardenasl\Ci4ApiCore\Mappers\ResponseMapperInterface;
use dcardenasl\Ci4ApiCore\Repositories\RepositoryInterface;
use dcardenasl\Ci4ApiCore\Services\BaseCrudService;

/**
 * @extends BaseCrudService<EventEntity>
 */
class EventService extends BaseCrudService implements EventServiceInterface
{
    use HasLocalizedTranslations;

    /**
     * @param RepositoryInterface<EventEntity> $eventRepository
     */
    public function __construct(
        RepositoryInterface $eventRepository,
        ResponseMapperInterface $responseMapper,
        LocalizedTranslationStore $translationStore,
    ) {
        parent::__construct($eventRepository, $responseMapper);
        $this->translationStore = $translationStore;
        $this->localizedResourceType = 'event';
    }
}
