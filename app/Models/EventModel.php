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

    protected $allowedFields = ['uuid', 'title', 'event_type', 'description', 'start_time', 'end_time', 'venue', 'capacity', 'available_spots', 'status'];

    /** @var array<int, string> */
    protected array $searchableFields = ['title', 'venue'];

    /** @var array<int, string> */
    protected array $filterableFields = ['id', 'event_type', 'start_time', 'end_time', 'capacity', 'available_spots', 'status'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at', 'title', 'event_type', 'start_time', 'end_time', 'venue', 'capacity', 'available_spots', 'status'];

    protected $validationRules = [
        'uuid' => 'required|string|max_length[255]|is_unique[events.uuid]',
        'title' => 'required|string|max_length[255]',
        'event_type' => 'required|in_list[function,festival,course,workshop,other]',
        'description' => 'required|string',
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
