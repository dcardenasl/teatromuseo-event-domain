<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Events;

use App\DTO\Request\Events\EventTypeCreateRequestDTO;
use App\DTO\Request\Events\EventTypeIndexRequestDTO;
use App\DTO\Request\Events\EventTypeUpdateRequestDTO;
use App\Interfaces\Events\EventTypeServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class EventTypeController extends ApiController
{
    protected EventTypeServiceInterface $eventTypeService;

    protected function resolveDefaultService(): EventTypeServiceInterface
    {
        $this->eventTypeService = Services::eventTypeService();

        return $this->eventTypeService;
    }

    protected array $statusCodes = [
        'store' => 201,
    ];

    public function index(): ResponseInterface
    {
        return $this->handleRequest('index', EventTypeIndexRequestDTO::class);
    }

    public function checkSlug(): ResponseInterface
    {
        $this->resolveDefaultService();
        $slugValue = $this->request->getGet('slug');
        $localeValue = $this->request->getGet('locale') ?? $this->request->getGet('language_code');
        $currentIdValue = $this->request->getGet('current_id');
        $slug = is_scalar($slugValue) ? (string) $slugValue : '';
        $locale = is_scalar($localeValue) ? (string) $localeValue : '';
        $currentId = is_numeric($currentIdValue) ? (int) $currentIdValue : 0;

        return $this->response->setJSON([
            'available' => $this->eventTypeService->isSlugAvailable($slug, $locale, $currentId),
        ]);
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest('store', EventTypeCreateRequestDTO::class);
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            fn (EventTypeUpdateRequestDTO $dto, $context): mixed => $this->eventTypeService->update($id, $dto, $context),
            EventTypeUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(fn ($dto, $context) => $this->eventTypeService->show($id, $context));
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(fn ($dto, $context) => $this->eventTypeService->destroy($id, $context));
    }
}
