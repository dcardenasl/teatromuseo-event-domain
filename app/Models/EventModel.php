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

    protected $allowedFields = ['uuid', 'title', 'event_type', 'description', 'cover_file_id', 'gallery_file_ids', 'start_time', 'end_time', 'venue', 'capacity', 'available_spots', 'status'];

    /** @var array<int, string> */
    protected array $searchableFields = ['title', 'venue'];

    /** @var array<int, string> */
    protected array $filterableFields = ['id', 'event_type', 'start_time', 'end_time', 'capacity', 'available_spots', 'status'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at', 'title', 'event_type', 'start_time', 'end_time', 'venue', 'capacity', 'available_spots', 'status'];

    protected $validationRules = [
        // The model owns UUID generation. Keeping this optional at validation
        // time allows the beforeInsert hook to create it atomically.
        'uuid' => 'permit_empty|string|max_length[255]|is_unique[events.uuid]',
        'title' => 'required|string|max_length[255]',
        'event_type' => 'required|string|max_length[80]|is_not_unique[event_types.slug]',
        'description' => 'required|string',
        'cover_file_id' => 'permit_empty|integer',
        'gallery_file_ids' => 'permit_empty|string',
        'start_time' => 'permit_empty|valid_date',
        'end_time' => 'permit_empty|valid_date',
        'venue' => 'permit_empty|string|max_length[255]',
        'capacity' => 'permit_empty|integer',
        'available_spots' => 'permit_empty|integer',
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
}
