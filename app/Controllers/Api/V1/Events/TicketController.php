<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Events;

use App\DTO\Request\Events\TicketCreateRequestDTO;
use App\DTO\Request\Events\TicketIndexRequestDTO;
use App\DTO\Request\Events\TicketUpdateRequestDTO;
use App\Interfaces\Events\TicketServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class TicketController extends ApiController
{
    protected TicketServiceInterface $ticketService;

    protected function resolveDefaultService(): object
    {
        $this->ticketService = Services::ticketService();

        return $this->ticketService;
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function index(): ResponseInterface
    {
        return $this->handleRequest('index', TicketIndexRequestDTO::class);
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest('store', TicketCreateRequestDTO::class);
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            fn ($dto, $context) => $this->ticketService->update($id, $dto, $context),
            TicketUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(fn ($dto, $context) => $this->ticketService->show($id, $context));
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(fn ($dto, $context) => $this->ticketService->destroy($id, $context));
    }
}
