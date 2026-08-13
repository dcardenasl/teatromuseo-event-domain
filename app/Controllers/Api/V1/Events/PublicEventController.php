<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Events;

use App\DTO\Request\Events\EventTypeIndexRequestDTO;
use App\Interfaces\Events\EventServiceInterface;
use App\Services\Events\EventMediaResolutionService;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;
use dcardenasl\Ci4ApiCore\Traits\SparseFieldsetTrait;

class PublicEventController extends ApiController
{
    use SparseFieldsetTrait;

    private const DETAIL_FIELDS = [
        'id', 'uuid', 'title', 'event_type', 'slug', 'slugs', 'cover_file_id',
        'cover_image', 'gallery_file_ids', 'gallery_images', 'description',
        'translations', 'localized', 'occurrences', 'status', 'created_at', 'updated_at',
    ];
    protected EventServiceInterface $eventService;

    protected EventMediaResolutionService $mediaResolutionService;

    protected function resolveDefaultService(): EventServiceInterface
    {
        $this->eventService = Services::eventService();
        $this->mediaResolutionService = Services::eventMediaResolutionService();

        return $this->eventService;
    }

    /**
     * Public detail: resolves a numeric id, uuid, or per-locale routing slug.
     * Only published events are exposed.
     */
    public function show(string $idOrSlug): ResponseInterface
    {
        return $this->handleRequest(
            function (mixed $_, SecurityContext $context) use ($idOrSlug): mixed {
                $fields = $this->parseFieldsParam(self::DETAIL_FIELDS);
                $data = $this->eventService->getPublicByIdOrSlug($idOrSlug);
                $resolved = $this->mediaResolutionService->resolveMediaFields($data);
                return $this->sparseFilter($resolved, $fields);
            }
        );
    }

    /**
     * Public catalogue of active event types used by listing filters.
     */
    public function types(): ResponseInterface
    {
        return $this->handleRequest(function (): mixed {
            return Services::eventTypeService()->index(
                Services::requestDtoFactory()->make(EventTypeIndexRequestDTO::class, [
                    'page' => 1,
                    'per_page' => 100,
                    'sort' => 'sort_order',
                    'filter' => ['is_active' => '1'],
                ])
            );
        });
    }
}
