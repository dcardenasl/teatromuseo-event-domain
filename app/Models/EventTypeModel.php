<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\EventTypeEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class EventTypeModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'event_types';
    protected $primaryKey = 'id';
    protected $returnType = EventTypeEntity::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;

    protected $allowedFields = ['slug', 'name', 'sort_order', 'is_active'];

    /** @var array<int, string> */
    protected array $searchableFields = ['slug', 'name'];

    /** @var array<int, string> */
    protected array $filterableFields = ['id', 'sort_order', 'is_active'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at', 'slug', 'sort_order', 'is_active'];

    protected $validationRules = [
        'slug' => 'required|string|max_length[255]|is_unique[event_types.slug]',
        'name' => 'required|string|max_length[255]',
        'sort_order' => 'required|integer',
        'is_active' => 'required|boolean_like',
    ];
}
