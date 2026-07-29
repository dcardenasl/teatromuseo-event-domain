<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Events;

use App\DTO\Request\Events\EventIndexRequestDTO;
use App\Interfaces\Events\EventServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class PublicEventController extends ApiController
{
    protected EventServiceInterface $eventService;

    protected function resolveDefaultService(): EventServiceInterface
    {
        $this->eventService = Services::eventService();

        return $this->eventService;
    }

    /**
     * Public detail: resolves a numeric id, uuid, or per-locale routing slug.
     * Only published events are exposed.
     */
    public function show(string $idOrSlug): ResponseInterface
    {
        return $this->handleRequest(
            fn (array $dto, SecurityContext $context): mixed => $this->eventService->getPublicByIdOrSlug($idOrSlug)
        );
    }

    public function index(): ResponseInterface
    {
        $request = service('request');
        $filter = $request->getGet('filter');
        $filter = is_array($filter) ? $filter : [];
        $filter['status'] = 'published';

        $sort = $request->getGet('sort');
        if (! is_string($sort) || trim($sort) === '') {
            $sort = 'start_time';
        }

        $request->setGlobal('get', array_merge($request->getGet(), [
            'filter' => $filter,
            'sort' => $sort,
        ]));

        return $this->handleRequest(
            function (EventIndexRequestDTO $dto, SecurityContext $context): mixed {
                $result = $this->eventService->index($dto, $context)->toArray();

                if (is_array($result['data'] ?? null)) {
                    foreach ($result['data'] as $key => $event) {
                        $result['data'][$key] = $event instanceof DataTransferObjectInterface
                            ? $event->toArray()
                            : (array) $event;
                    }
                }

                return $result;
            },
            EventIndexRequestDTO::class
        );
    }
}
