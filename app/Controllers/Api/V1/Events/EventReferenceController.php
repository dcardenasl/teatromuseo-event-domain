<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Events;

use App\DTO\Request\Events\EventReferenceCreateRequestDTO;
use App\DTO\Request\Events\EventReferenceIndexRequestDTO;
use App\DTO\Request\Events\EventReferenceUpdateRequestDTO;
use App\Interfaces\Events\EventReferenceServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
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
        return $this->handleRequest(
            function (EventReferenceIndexRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('eventReference.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->eventReferenceService->index($dto, $context);
            },
            EventReferenceIndexRequestDTO::class
        );
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest(
            function (EventReferenceCreateRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('eventReference.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->eventReferenceService->store($dto, $context);
            },
            EventReferenceCreateRequestDTO::class
        );
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (EventReferenceUpdateRequestDTO $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('eventReference.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->eventReferenceService->update($id, $dto, $context);
            },
            EventReferenceUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('eventReference.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->eventReferenceService->show($id, $context);
            }
        );
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('eventReference.delete')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->eventReferenceService->destroy($id, $context);
            }
        );
    }
}
