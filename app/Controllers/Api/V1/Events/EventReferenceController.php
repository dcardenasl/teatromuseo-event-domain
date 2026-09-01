<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Events;

use App\DTO\Request\Events\EventReferenceCreateRequestDTO;
use App\DTO\Request\Events\EventReferenceIndexRequestDTO;
use App\DTO\Request\Events\EventReferenceUpdateRequestDTO;
use App\Interfaces\Events\EventReferenceServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class EventReferenceController extends ApiController
{
    protected EventReferenceServiceInterface $eventReferenceService;

    protected function resolveDefaultService(): EventReferenceServiceInterface
    {
        $this->eventReferenceService = Services::eventReferenceService();

        return $this->eventReferenceService;
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function index(): ResponseInterface
    {
        return $this->handleRequest('index', EventReferenceIndexRequestDTO::class);
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest('store', EventReferenceCreateRequestDTO::class);
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            fn ($dto, $context) => $this->eventReferenceService->update($id, $dto, $context),
            EventReferenceUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(fn ($dto, $context) => $this->eventReferenceService->show($id, $context));
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(fn ($dto, $context) => $this->eventReferenceService->destroy($id, $context));
    }
}
