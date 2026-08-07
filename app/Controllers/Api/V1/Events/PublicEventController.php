<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Events;

use App\DTO\Request\Events\EventIndexRequestDTO;
use App\DTO\Request\Events\EventTypeIndexRequestDTO;
use App\Interfaces\Events\EventServiceInterface;
use App\Services\Events\EventMediaResolutionService;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class PublicEventController extends ApiController
{
    protected EventServiceInterface $eventService;

    protected EventMediaResolutionService $mediaResolutionService;

    protected function resolveDefaultService(): EventServiceInterface
    {
        $this->eventService = Services::eventService();
        $this->mediaResolutionService = Services::eventMediaResolutionService();

        return $this->eventService;
    }

    /**
     * Public detail: resolves a numeric id, uuid, or per-locale routing slug.
     * Only published events are exposed.
     */
    public function show(string $idOrSlug): ResponseInterface
    {
        return $this->handleRequest(
            fn (mixed $_, SecurityContext $context): mixed => $this->mediaResolutionService->resolveMediaFields(
                $this->eventService->getPublicByIdOrSlug($idOrSlug)
            )
        );
    }

    public function index(): ResponseInterface
    {
        return $this->handleRequest(
            function (EventIndexRequestDTO $dto, SecurityContext $context): mixed {
                $result = $this->eventService->indexPublicCartelera($dto, $context)->toArray();

                if (is_array($result['data'] ?? null)) {
                    foreach ($result['data'] as $key => $event) {
                        $eventArray = $event instanceof DataTransferObjectInterface
                            ? $event->toArray()
                            : (array) $event;
                        $result['data'][$key] = $this->mediaResolutionService->resolveMediaFields($eventArray);
                    }
                }

                return $result;
            },
            EventIndexRequestDTO::class
        );
    }

    /**
     * Public catalogue of active event types used by listing filters.
     */
    public function types(): ResponseInterface
    {
        return $this->handleRequest(function (): mixed {
            return Services::eventTypeService()->index(
                Services::requestDtoFactory()->make(EventTypeIndexRequestDTO::class, [
                    'page' => 1,
                    'per_page' => 100,
                    'sort' => 'sort_order',
                    'filter' => ['is_active' => '1'],
                ])
            );
        });
    }
}
