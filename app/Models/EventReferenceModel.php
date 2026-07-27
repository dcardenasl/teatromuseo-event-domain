<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\EventReferenceEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class EventReferenceModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'event_references';
    protected $primaryKey = 'id';
    protected $returnType = EventReferenceEntity::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;

    protected $allowedFields = ['event_id', 'source_system', 'source_type', 'source_id', 'relation', 'metadata'];

    /** @var array<int, string> */
    protected array $searchableFields = [];

    /** @var array<int, string> */
    protected array $filterableFields = ['id', 'source_system', 'source_type', 'source_id', 'relation'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at', 'source_system', 'source_type', 'source_id', 'relation'];

    protected $validationRules = [
        'event_id' => 'required|is_natural_no_zero|is_not_unique[events.id]',
        'source_system' => 'required|string|max_length[255]',
        'source_type' => 'required|string|max_length[255]',
        'source_id' => 'required|string|max_length[255]',
        'relation' => 'required|string|max_length[255]',
        'metadata' => 'permit_empty',
    ];
}
