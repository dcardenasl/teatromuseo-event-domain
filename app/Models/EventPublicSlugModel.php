<?php

declare(strict_types=1);

namespace App\Models;

use dcardenasl\Ci4ApiCore\Models\BaseAuditableModel;

class EventPublicSlugModel extends BaseAuditableModel
{
    protected $table = 'event_public_slugs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = ['resource_type', 'resource_id', 'locale', 'slug'];
    protected $useTimestamps = true;

    protected $validationRules = [
        'resource_type' => 'required|max_length[80]',
        'resource_id'   => 'required|is_natural_no_zero',
        'locale'        => 'required|max_length[35]',
        'slug'          => 'required|max_length[191]',
    ];
}
