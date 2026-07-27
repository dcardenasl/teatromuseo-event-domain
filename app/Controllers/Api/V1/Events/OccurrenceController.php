<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Events;

use App\DTO\Request\Events\OccurrenceCreateRequestDTO;
use App\DTO\Request\Events\OccurrenceIndexRequestDTO;
use App\DTO\Request\Events\OccurrenceUpdateRequestDTO;
use App\Interfaces\Events\OccurrenceServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
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
        return $this->handleRequest(
            function (OccurrenceIndexRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('occurrence.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->occurrenceService->index($dto, $context);
            },
            OccurrenceIndexRequestDTO::class
        );
    }

    public function create(): ResponseInterface
    {
        return $this->handleRequest(
            function (OccurrenceCreateRequestDTO $dto, SecurityContext $context): mixed {
                if (!$context->hasPermission('occurrence.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->occurrenceService->store($dto, $context);
            },
            OccurrenceCreateRequestDTO::class
        );
    }

    public function update(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (OccurrenceUpdateRequestDTO $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('occurrence.write')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->occurrenceService->update($id, $dto, $context);
            },
            OccurrenceUpdateRequestDTO::class
        );
    }

    public function show(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('occurrence.read')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->occurrenceService->show($id, $context);
            }
        );
    }

    public function delete(int $id): ResponseInterface
    {
        return $this->handleRequest(
            function (array $dto, SecurityContext $context) use ($id): mixed {
                if (!$context->hasPermission('occurrence.delete')) {
                    throw new \dcardenasl\Ci4ApiCore\Exceptions\AuthorizationException(lang('Api.forbidden'));
                }
                return $this->occurrenceService->destroy($id, $context);
            }
        );
    }
}
