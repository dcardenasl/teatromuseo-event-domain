<?php

declare(strict_types=1);

return [
    'create_success' => 'Booking created successfully.',
    'update_success' => 'Booking updated successfully.',
    'delete_success' => 'Booking deleted successfully.',
    'not_found'      => 'Booking not found.',
    'fields'         => [
        'uuid' => 'Uuid',
        'user_id' => 'User id',
        'guest_email' => 'Guest email',
        'total_amount' => 'Total amount',
        'status' => 'Status',
        'reserved_until' => 'Reserved until',
    ],
    'invalid_quantity'      => 'Provide a valid ticket category and quantity greater than zero.',
    'ticket_type_not_found' => 'Ticket category not found.',
    'ticket_type_sold_out'  => 'Not enough ticket category spots available.',
    'event_not_found'       => 'Event not found.',
    'event_sold_out'        => 'Not enough event spots available.',
    'occurrence_not_found'  => 'Scheduled occurrence not found.',
    'occurrence_sold_out'   => 'Not enough scheduled occurrence spots available.',
];
