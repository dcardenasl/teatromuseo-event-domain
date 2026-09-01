<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Libraries\Hub\HubClient;

/**
 * Resolves the Hub file IDs referenced by a public event payload
 * (`cover_file_id`, `gallery_file_ids`) into full Hub file metadata
 * (url + variants), via {@see HubClient::resolvePublicFileMeta()}.
 *
 * Extracted from `PublicEventController::resolveMediaFields()` — controllers
 * must not reach out to external HTTP clients directly (LAYER-01); that is a
 * Service's job. Mirrors the shape of the sibling {@see FileUsageService}: a
 * small, focused utility service with a single collaborator, no CRUD
 * interface, wired as a concrete class return in `EventsDomainServices`.
 *
 * `catalog-domain`'s `PublicCollectionItemController::resolveMediaFields()`
 * has the same duplicated logic, still inline in its controller (not yet
 * extracted) — see event-domain TASKS.md LAYER-01 for the cross-repo note.
 */
class EventMediaResolutionService
{
    public function __construct(private readonly HubClient $hubClient)
    {
    }

    /**
     * @param  array<string, mixed>  $event
     * @return array<string, mixed>
     */
    public function resolveMediaFields(array $event): array
    {
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
            $metaMap = $this->hubClient->resolvePublicFileMeta($fileIds);
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
