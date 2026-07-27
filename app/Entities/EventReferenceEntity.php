<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class EventReferenceEntity extends Entity
{
    protected $casts = [
        'id' => 'integer',
        'event_id' => 'int',
        'source_system' => 'string',
        'source_type' => 'string',
        'source_id' => 'string',
        'relation' => 'string',
        'metadata' => 'json',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
}
