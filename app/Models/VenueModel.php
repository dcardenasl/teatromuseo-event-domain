<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\VenueEntity;
use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;
use dcardenasl\Ci4ApiCore\Models\Traits\Filterable;
use dcardenasl\Ci4ApiCore\Models\Traits\Searchable;

class VenueModel extends BaseAuditableModel
{
    use Filterable;
    use Searchable;

    protected $table = 'venues';
    protected $primaryKey = 'id';
    protected $returnType = VenueEntity::class;
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;

    protected $allowedFields = ['name', 'slug', 'description', 'capacity', 'is_active'];

    /** @var array<int, string> */
    protected array $searchableFields = ['name', 'slug'];

    /** @var array<int, string> */
    protected array $filterableFields = ['id', 'capacity', 'is_active'];

    /** @var array<int, string> */
    protected array $sortableFields = ['id', 'created_at', 'name', 'slug', 'capacity', 'is_active'];

    protected $validationRules = [
        'name' => 'required|string|max_length[255]',
        'slug' => 'required|string|max_length[255]|is_unique[venues.slug]',
        'description' => 'permit_empty|string',
        'capacity' => 'permit_empty|integer',
        'is_active' => 'required|boolean_like',
    ];
}
