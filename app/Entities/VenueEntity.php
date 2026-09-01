<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class VenueEntity extends Entity
{
    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'slug' => 'string',
        'description' => 'string',
        'capacity' => 'int',
        'is_active' => 'bool',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
}
