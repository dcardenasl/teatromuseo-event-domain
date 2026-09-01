<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class OccurrenceEntity extends Entity
{
    protected $casts = [
        'id' => 'integer',
        'event_id' => 'int',
        'venue_id' => 'int',
        'start_time' => 'string',
        'end_time' => 'string',
        'status' => 'string',
        'capacity' => 'int',
        'available_spots' => 'int',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
}
