<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\OccurrenceEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class OccurrenceModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'occurrences';
    protected $primaryKey = 'id';
    protected $returnType = OccurrenceEntity::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;

    protected $allowedFields = ['event_id', 'venue_id', 'start_time', 'end_time', 'status', 'capacity', 'available_spots'];

    /** @var array<int, string> */
    protected array $searchableFields = [];

    /** @var array<int, string> */
    protected array $filterableFields = ['id', 'status'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at', 'status'];

    protected $validationRules = [
        'event_id' => 'required|is_natural_no_zero|is_not_unique[events.id]',
        'venue_id' => 'permit_empty|is_natural_no_zero|is_not_unique[venues.id]',
        'start_time' => 'required|valid_date',
        'end_time' => 'required|valid_date',
        'status' => 'required|string|max_length[255]',
        'capacity' => 'required|integer',
        'available_spots' => 'required|integer',
    ];
}
