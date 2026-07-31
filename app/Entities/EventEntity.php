<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class EventEntity extends Entity
{
    protected $casts = [
        'id' => 'integer',
        'uuid' => 'string',
        'title' => 'string',
        'event_type' => 'string',
        'description' => 'string',
        'cover_file_id' => '?integer',
        'gallery_file_ids' => '?string',
        'start_time' => '?string',
        'end_time' => '?string',
        'venue' => '?string',
        'capacity' => '?int',
        'available_spots' => '?int',
        'status' => 'string',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
}
