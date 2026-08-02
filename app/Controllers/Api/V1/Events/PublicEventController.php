<?php

declare(strict_types=1);

namespace App\Controllers\Api\V1\Events;

use App\DTO\Request\Events\EventIndexRequestDTO;
use App\Interfaces\Events\EventServiceInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use dcardenasl\Ci4ApiCore\Dto\DataTransferObjectInterface;
use dcardenasl\Ci4ApiCore\Dto\SecurityContext;
use dcardenasl\Ci4ApiCore\Http\ApiController;

class PublicEventController extends ApiController
{
    protected EventServiceInterface $eventService;

    protected function resolveDefaultService(): EventServiceInterface
    {
        $this->eventService = Services::eventService();

        return $this->eventService;
    }

    /**
     * Public detail: resolves a numeric id, uuid, or per-locale routing slug.
     * Only published events are exposed.
     */
    public function show(string $idOrSlug): ResponseInterface
    {
        return $this->handleRequest(
            fn (array $dto, SecurityContext $context): mixed => $this->resolveMediaFields(
                $this->eventService->getPublicByIdOrSlug($idOrSlug)
            )
        );
    }

    public function index(): ResponseInterface
    {
        $request = service('request');
        $filter = $request->getGet('filter');
        $filter = is_array($filter) ? $filter : [];
        $filter['status'] = 'published';

        $sort = $request->getGet('sort');
        if (! is_string($sort) || trim($sort) === '') {
            $sort = 'start_time';
        }

        $request->setGlobal('get', array_merge($request->getGet(), [
            'filter' => $filter,
            'sort' => $sort,
        ]));

        return $this->handleRequest(
            function (EventIndexRequestDTO $dto, SecurityContext $context): mixed {
                $result = $this->eventService->indexPublicCartelera($dto, $context)->toArray();

                if (is_array($result['data'] ?? null)) {
                    foreach ($result['data'] as $key => $event) {
                        $eventArray = $event instanceof DataTransferObjectInterface
                            ? $event->toArray()
                            : (array) $event;
                        $result['data'][$key] = $this->resolveMediaFields($eventArray);
                    }
                }

                return $result;
            },
            EventIndexRequestDTO::class
        );
    }

    /**
     * Helper to resolve cover image and gallery file IDs to Hub file metadata.
     *
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>
     */
    private function resolveMediaFields(array $event): array
    {
        $hub = Services::hubClient();

        $fileIds = [];
        if (isset($event['cover_file_id']) && (int) $event['cover_file_id'] > 0) {
            $fileIds[] = (int) $event['cover_file_id'];
        }

        $galleryIds = [];
        if (isset($event['gallery_file_ids']) && is_string($event['gallery_file_ids']) && trim($event['gallery_file_ids']) !== '') {
            $rawIds = explode(',', $event['gallery_file_ids']);
            foreach ($rawIds as $rawId) {
                $id = (int) trim($rawId);
                if ($id > 0) {
                    $fileIds[] = $id;
                    $galleryIds[] = $id;
                }
            }
        }

        $metaMap = [];
        if (!empty($fileIds)) {
            $metaMap = $hub->resolvePublicFileMeta($fileIds);
        }

        $event['cover_image'] = null;
        if (isset($event['cover_file_id']) && (int) $event['cover_file_id'] > 0) {
            $fileId = (int) $event['cover_file_id'];
            $meta = $metaMap[$fileId] ?? null;
            if ($meta) {
                $event['cover_image'] = [
                    'source_kind' => 'hub_file',
                    'file_id'     => $fileId,
                    'url'         => $meta['url'] ?? null,
                    'variants'    => is_string($meta['variants'] ?? null) ? json_decode($meta['variants'], true) : ($meta['variants'] ?? null),
                ];
            }
        }

        $gallery = [];
        foreach ($galleryIds as $fileId) {
            $meta = $metaMap[$fileId] ?? null;
            if ($meta) {
                $gallery[] = [
                    'source_kind' => 'hub_file',
                    'file_id'     => $fileId,
                    'url'         => $meta['url'] ?? null,
                    'variants'    => is_string($meta['variants'] ?? null) ? json_decode($meta['variants'], true) : ($meta['variants'] ?? null),
                ];
            }
        }
        $event['gallery_images'] = $gallery;

        return $event;
    }
}
