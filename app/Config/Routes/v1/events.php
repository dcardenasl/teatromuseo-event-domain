<?php

declare(strict_types=1);

/** @var \CodeIgniter\Router\RouteCollection $routes */

$routes->group('events', ['namespace' => '\App\Controllers\Api\V1\Events'], function ($routes): void {

    // Auth & Admin Protected Group
    $routes->group('', ['filter' => ['domainauth', 'throttle']], function ($routes): void {
        // Event Routes
        $routes->group('', ['filter' => 'permission:events.read'], function ($routes): void {
            $routes->get('events', 'EventController::index');
            $routes->get('events/(:num)', 'EventController::show/$1');
        });
        $routes->group('', ['filter' => 'permission:events.write'], function ($routes): void {
            $routes->post('events', 'EventController::create');
            $routes->put('events/(:num)', 'EventController::update/$1');
            $routes->delete('events/(:num)', 'EventController::delete/$1');
        });

        // TicketType Routes
        $routes->group('', ['filter' => 'permission:ticket-types.read'], function ($routes): void {
            $routes->get('ticket-types', 'TicketTypeController::index');
            $routes->get('ticket-types/(:num)', 'TicketTypeController::show/$1');
        });
        $routes->group('', ['filter' => 'permission:ticket-types.write'], function ($routes): void {
            $routes->post('ticket-types', 'TicketTypeController::create');
            $routes->put('ticket-types/(:num)', 'TicketTypeController::update/$1');
            $routes->delete('ticket-types/(:num)', 'TicketTypeController::delete/$1');
        });

        // Booking Routes
        $routes->group('', ['filter' => 'permission:bookings.read'], function ($routes): void {
            $routes->get('bookings', 'BookingController::index');
            $routes->get('bookings/(:num)', 'BookingController::show/$1');
        });
        $routes->group('', ['filter' => 'permission:bookings.write'], function ($routes): void {
            $routes->post('bookings', 'BookingController::create');
            $routes->put('bookings/(:num)', 'BookingController::update/$1');
            $routes->delete('bookings/(:num)', 'BookingController::delete/$1');
        });

        // Ticket Routes
        $routes->group('', ['filter' => 'permission:tickets.read'], function ($routes): void {
            $routes->get('tickets', 'TicketController::index');
            $routes->get('tickets/(:num)', 'TicketController::show/$1');
        });
        $routes->group('', ['filter' => 'permission:tickets.write'], function ($routes): void {
            $routes->post('tickets', 'TicketController::create');
            $routes->put('tickets/(:num)', 'TicketController::update/$1');
            $routes->delete('tickets/(:num)', 'TicketController::delete/$1');
        });
    });
});
