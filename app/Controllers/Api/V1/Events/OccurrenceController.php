<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Events;

use App\DTO\Request\Events\OccurrenceCreateRequestDTO;
use App\DTO\Request\Events\OccurrenceIndexRequestDTO;
use App\DTO\Request\Events\OccurrenceUpdateRequestDTO;
use App\Interfaces\Events\OccurrenceServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class OccurrenceController extends ApiController
{
    protected OccurrenceServiceInterface $occurrenceService;

    protected function resolveDefaultService(): OccurrenceServiceInterface
    {
        $this->occurrenceService = Services::occurrenceService();

        return $this->occurrenceService;
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function index(): ResponseInterface
    {
        return $this->handleRequest('index', OccurrenceIndexRequestDTO::class);
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest('store', OccurrenceCreateRequestDTO::class);
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            fn ($dto, $context) => $this->occurrenceService->update($id, $dto, $context),
            OccurrenceUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(fn ($dto, $context) => $this->occurrenceService->show($id, $context));
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(fn ($dto, $context) => $this->occurrenceService->destroy($id, $context));
    }
}
