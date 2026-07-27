<?php

declare(strict_types=1);

return [
    'create_success' => 'Booking creado(a) exitosamente.',
    'update_success' => 'Booking actualizado(a) exitosamente.',
    'delete_success' => 'Booking eliminado(a) exitosamente.',
    'not_found'      => 'Booking no encontrado(a).',
    'fields'         => [
        'uuid' => 'Uuid',
        'user_id' => 'User id',
        'guest_email' => 'Guest email',
        'total_amount' => 'Total amount',
        'status' => 'Status',
        'reserved_until' => 'Reserved until',
    ],
    'invalid_quantity'      => 'Proporcione una categoría de ticket válida y cantidad mayor a cero.',
    'ticket_type_not_found' => 'Categoría de ticket no encontrada.',
    'ticket_type_sold_out'  => 'No hay suficientes espacios disponibles en esta categoría de ticket.',
    'event_not_found'       => 'Evento no encontrado.',
    'event_sold_out'        => 'No hay suficientes espacios disponibles para el evento.',
    'occurrence_not_found'  => 'Función programada no encontrada.',
    'occurrence_sold_out'   => 'No hay suficientes espacios disponibles para la función programada.',
];
