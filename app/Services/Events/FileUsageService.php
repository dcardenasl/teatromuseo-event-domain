<?php

declare(strict_types=1);

namespace App\Services\Events;

use CodeIgniter\Database\BaseConnection;

/**
 * Reports all events rows that reference a given Hub file ID, via
 * cover_file_id or the gallery_file_ids CSV column. Mirrors the "shared
 * usages contract" (source, resource, resource_id, role, label)
 * cms-domain's FileUsageService established, so the Hub's
 * DomainFileUsageClient can merge results from every domain uniformly.
 *
 * @phpstan-type UsageItem array{source: string, resource: string, resource_id: int, role: string, label: string|null}
 */
class FileUsageService
{
    /** @var BaseConnection<mixed, mixed> */
    private BaseConnection $db;

    /**
     * @param BaseConnection<mixed, mixed> $db
     */
    public function __construct(BaseConnection $db)
    {
        $this->db = $db;
    }

    /**
     * @return list<UsageItem>
     */
    public function getUsagesByHubFileId(int $hubFileId): array
    {
        // gallery_file_ids is a plain CSV column (no FIND_IN_SET/JSON index),
        // so a SQL substring match would false-positive (file 1 matching
        // "21" or "12,1"). Narrow with a cheap SQL prefilter (candidates
        // whose CSV *contains the digits somewhere*, or whose cover matches
        // exactly), then verify exact membership in PHP.
        $result = $this->db->table('events')
            ->select('id, title, cover_file_id, gallery_file_ids')
            ->where('deleted_at', null)
            ->groupStart()
                ->where('cover_file_id', $hubFileId)
                ->orLike('gallery_file_ids', (string) $hubFileId, 'both')
            ->groupEnd()
            ->get();
        $rows = $result ? $result->getResultArray() : [];

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
