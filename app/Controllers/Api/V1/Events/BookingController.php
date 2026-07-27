<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Events;

use App\DTO\Request\Events\BookingCreateRequestDTO;
use App\DTO\Request\Events\BookingIndexRequestDTO;
use App\DTO\Request\Events\BookingUpdateRequestDTO;
use App\Interfaces\Events\BookingServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class BookingController extends ApiController
{
    protected BookingServiceInterface $bookingService;

    protected function resolveDefaultService(): object
    {
        $this->bookingService = Services::bookingService();

        return $this->bookingService;
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function index(): ResponseInterface
    {
        return $this->handleRequest('index', BookingIndexRequestDTO::class);
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest('store', BookingCreateRequestDTO::class);
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            fn ($dto, $context) => $this->bookingService->update($id, $dto, $context),
            BookingUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(fn ($dto, $context) => $this->bookingService->show($id, $context));
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(fn ($dto, $context) => $this->bookingService->destroy($id, $context));
    }
}
