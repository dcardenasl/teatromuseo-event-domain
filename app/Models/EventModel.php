<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\EventEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class EventModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'events';
    protected $primaryKey = 'id';
    protected $returnType = EventEntity::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;

    protected $allowedFields = ['uuid', 'title', 'event_type', 'description', 'cover_file_id', 'gallery_file_ids', 'status'];

    /** @var array<int, string> */
    protected array $searchableFields = ['title'];

    /** @var array<int, string> */
    protected array $filterableFields = ['id', 'event_type', 'status'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at', 'title', 'event_type', 'status'];

    protected $validationRules = [
        // The model owns UUID generation. Keeping this optional at validation
        // time allows the beforeInsert hook to create it atomically.
        'uuid' => 'permit_empty|string|max_length[255]|is_unique[events.uuid]',
        'title' => 'required|string|max_length[255]',
        'event_type' => 'required|string|max_length[80]|is_not_unique[event_types.slug]',
        'description' => 'required|string',
        'cover_file_id' => 'permit_empty|integer',
        'gallery_file_ids' => 'permit_empty|string',
    ];

    protected $beforeInsert = ['generateUuid'];

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function generateUuid(array $data): array
    {
        if (empty($data['data']['uuid'])) {
            $data['data']['uuid'] = \dcardenasl\Ci4ApiCore\Security\Token::generateUuid();
        }
        return $data;
    }

    /**
     * Rows referencing a given Hub file id via `cover_file_id` or the
     * `gallery_file_ids` CSV column, for FileUsageService's usage-reporting
     * endpoint. `gallery_file_ids` is a plain CSV column (no FIND_IN_SET/JSON
     * index), so a SQL substring match would false-positive (file 1 matching
     * "21" or "12,1") — this only narrows candidates; the caller verifies
     * exact membership in PHP.
     *
     * @return list<array{id: int|string, title: mixed, cover_file_id: mixed, gallery_file_ids: mixed}>
     */
    public function findReferencingHubFile(int $hubFileId): array
    {
        $result = $this->builder()
            ->select('id, title, cover_file_id, gallery_file_ids')
            ->where('deleted_at', null)
            ->groupStart()
                ->where('cover_file_id', $hubFileId)
                ->orLike('gallery_file_ids', (string) $hubFileId, 'both')
            ->groupEnd()
            ->get();

        return $result ? array_values($result->getResultArray()) : [];
    }
}
