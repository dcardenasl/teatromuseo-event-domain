<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Events;

use App\DTO\Request\Events\TicketTypeCreateRequestDTO;
use App\DTO\Request\Events\TicketTypeIndexRequestDTO;
use App\DTO\Request\Events\TicketTypeUpdateRequestDTO;
use App\Interfaces\Events\TicketTypeServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class TicketTypeController extends ApiController
{
    protected TicketTypeServiceInterface $ticketTypeService;

    protected function resolveDefaultService(): object
    {
        $this->ticketTypeService = Services::ticketTypeService();

        return $this->ticketTypeService;
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function index(): ResponseInterface
    {
        return $this->handleRequest('index', TicketTypeIndexRequestDTO::class);
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest('store', TicketTypeCreateRequestDTO::class);
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            fn ($dto, $context) => $this->ticketTypeService->update($id, $dto, $context),
            TicketTypeUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(fn ($dto, $context) => $this->ticketTypeService->show($id, $context));
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(fn ($dto, $context) => $this->ticketTypeService->destroy($id, $context));
    }
}
