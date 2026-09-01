<?php

declare(strict_types=1);

namespace App\Entities;

use CodeIgniter\Entity\Entity;
use dcardenasl\Ci4ApiCore\DataCasts\DecimalCast;

class TicketTypeEntity extends Entity
{
    protected $castHandlers = [
        'decimal' => DecimalCast::class,
    ];

    protected $casts = [
        'id' => 'integer',
        'event_id' => 'int',
        'occurrence_id' => '?int',
        'name' => 'string',
        'price' => 'decimal',
        'capacity' => 'int',
        'available_spots' => 'int',
        'sales_start' => 'string',
        'sales_end' => 'string',
    ];

    protected $dates = ['created_at', 'updated_at', 'deleted_at'];
}
