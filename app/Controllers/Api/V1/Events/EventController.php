<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Events;

use App\DTO\Request\Events\EventCreateRequestDTO;
use App\DTO\Request\Events\EventIndexRequestDTO;
use App\DTO\Request\Events\EventUpdateRequestDTO;
use App\Interfaces\Events\EventServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class EventController extends ApiController
{
    protected EventServiceInterface $eventService;

    protected function resolveDefaultService(): object
    {
        $this->eventService = Services::eventService();

        return $this->eventService;
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function index(): ResponseInterface
    {
        return $this->handleRequest('index', EventIndexRequestDTO::class);
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest('store', EventCreateRequestDTO::class);
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            fn ($dto, $context) => $this->eventService->update($id, $dto, $context),
            EventUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(fn ($dto, $context) => $this->eventService->show($id, $context));
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(fn ($dto, $context) => $this->eventService->destroy($id, $context));
    }
}
