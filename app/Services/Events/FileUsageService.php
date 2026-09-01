<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Models\EventModel;

/**
 * Reports all events rows that reference a given Hub file ID, via
 * cover_file_id or the gallery_file_ids CSV column. Mirrors the "shared
 * usages contract" (source, resource, resource_id, role, label)
 * cms-domain's FileUsageService established, so the Hub's
 * DomainFileUsageClient can merge results from every domain uniformly.
 *
 * Queries through EventModel::findReferencingHubFile() rather than the raw
 * query builder (LAYER-03) — cms-domain's and catalog-domain's sibling
 * services still query their table directly via BaseConnection; that wasn't
 * changed here since it's out of scope for this app.
 *
 * @phpstan-type UsageItem array{source: string, resource: string, resource_id: int, role: string, label: string|null}
 */
class FileUsageService
{
    private EventModel $eventModel;

    public function __construct(EventModel $eventModel)
    {
        $this->eventModel = $eventModel;
    }

    /**
     * @return list<UsageItem>
     */
    public function getUsagesByHubFileId(int $hubFileId): array
    {
        $rows = $this->eventModel->findReferencingHubFile($hubFileId);

        $usages = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $title = isset($row['title']) ? (string) $row['title'] : null;
            $galleryIds = $this->parseCsvIds((string) ($row['gallery_file_ids'] ?? ''));

            if ((int) ($row['cover_file_id'] ?? 0) === $hubFileId) {
                $usages[] = ['source' => 'domain', 'resource' => 'events', 'resource_id' => $id, 'role' => 'cover', 'label' => $title];
            }
            if (in_array($hubFileId, $galleryIds, true)) {
                $usages[] = ['source' => 'domain', 'resource' => 'events', 'resource_id' => $id, 'role' => 'gallery', 'label' => $title];
            }
        }

        return $usages;
    }

    /**
     * @return list<int>
     */
    private function parseCsvIds(string $csv): array
    {
        if (trim($csv) === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (string $id): int => (int) trim($id),
            explode(',', $csv)
        ), static fn (int $id): bool => $id > 0));
    }
}
