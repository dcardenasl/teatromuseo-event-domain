<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class TicketEntity extends Entity
{
    protected $casts = [
        'id' => 'integer',
        'uuid' => 'string',
        'booking_id' => 'int',
        'ticket_type_id' => 'int',
        'holder_name' => 'string',
        'holder_email' => 'string',
        'qr_code_token' => 'string',
        'status' => 'string',
        'checked_in_at' => 'string',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
}
