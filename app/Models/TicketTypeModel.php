<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\TicketTypeEntity;
use App\Traits\Models\HasAvailableSpots;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class TicketTypeModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;
    use HasAvailableSpots;

    protected $table = 'ticket_types';
    protected $primaryKey = 'id';
    protected $returnType = TicketTypeEntity::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;

    protected $allowedFields = ['event_id', 'occurrence_id', 'name', 'price', 'capacity', 'available_spots', 'sales_start', 'sales_end'];

    /** @var array<int, string> */
    protected array $searchableFields = ['name'];

    /** @var array<int, string> */
    protected array $filterableFields = ['id', 'event_id', 'occurrence_id', 'price', 'capacity', 'available_spots', 'sales_start', 'sales_end'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at', 'event_id', 'occurrence_id', 'name', 'price', 'capacity', 'available_spots', 'sales_start', 'sales_end'];

    protected $validationRules = [
        'event_id' => 'required|is_natural_no_zero|is_not_unique[events.id]',
        'occurrence_id' => 'required|is_natural_no_zero|is_not_unique[occurrences.id]',
        'name' => 'required|string|max_length[255]',
        'price' => 'required|decimal',
        'capacity' => 'required|integer',
        'available_spots' => 'required|integer',
        'sales_start' => 'required|valid_date',
        'sales_end' => 'required|valid_date',
    ];
}
