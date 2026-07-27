<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Events;

use App\DTO\Request\Events\VenueCreateRequestDTO;
use App\DTO\Request\Events\VenueIndexRequestDTO;
use App\DTO\Request\Events\VenueUpdateRequestDTO;
use App\Interfaces\Events\VenueServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class VenueController extends ApiController
{
    protected VenueServiceInterface $venueService;

    protected function resolveDefaultService(): VenueServiceInterface
    {
        $this->venueService = Services::venueService();

        return $this->venueService;
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function index(): ResponseInterface
    {
        return $this->handleRequest('index', VenueIndexRequestDTO::class);
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest('store', VenueCreateRequestDTO::class);
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            fn ($dto, $context) => $this->venueService->update($id, $dto, $context),
            VenueUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(fn ($dto, $context) => $this->venueService->show($id, $context));
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(fn ($dto, $context) => $this->venueService->destroy($id, $context));
    }
}
