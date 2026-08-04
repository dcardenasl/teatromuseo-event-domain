<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class EventTypeEntity extends Entity
{
    protected $casts = [
        'id' => 'integer',
        'slug' => 'string',
        'name' => 'string',
        'sort_order' => 'int',
        'is_active' => 'bool',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
}
