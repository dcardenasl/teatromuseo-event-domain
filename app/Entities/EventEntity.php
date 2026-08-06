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
        'status' => 'string',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
}
