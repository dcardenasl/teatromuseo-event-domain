<?php

declare (strict_types=1);
/** @var \CodeIgniter\Router\RouteCollection $routes */
$routes->group('events', ['namespace' => '\App\Controllers\Api\V1\Events'], function ($routes): void {
    // Auth & Admin Protected Group
    $routes->group('', ['filter' => ['domainauth', 'throttle']], function ($routes): void {
        $routes->get('dashboard/summary', 'DashboardSummaryController::index');
        $routes->post('sort-orders', 'SortOrderController::reorder');

        // Event Routes
        $routes->group('', ['filter' => 'permission:event.events.read'], function ($routes): void {
            $routes->get('events', 'EventController::index');
            $routes->get('events/(:num)', 'EventController::show/$1');
        });
        $routes->group('', ['filter' => 'permission:event.events.write'], function ($routes): void {
            $routes->post('events', 'EventController::create');
            $routes->put('events/(:num)', 'EventController::update/$1');
        });
        $routes->group('', ['filter' => 'permission:event.events.delete'], function ($routes): void {
            $routes->delete('events/(:num)', 'EventController::delete/$1');
        });
        // Event Type Routes
        $routes->group('', ['filter' => 'permission:event.event-types.read'], function ($routes): void {
            $routes->get('event-types/check-slug', 'EventTypeController::checkSlug');
            $routes->get('event-types', 'EventTypeController::index');
            $routes->get('event-types/(:num)', 'EventTypeController::show/$1');
        });
        $routes->group('', ['filter' => 'permission:event.event-types.write'], function ($routes): void {
            $routes->post('event-types', 'EventTypeController::create');
            $routes->put('event-types/(:num)', 'EventTypeController::update/$1');
        });
        $routes->group('', ['filter' => 'permission:event.event-types.delete'], function ($routes): void {
            $routes->delete('event-types/(:num)', 'EventTypeController::delete/$1');
        });
        // Venue Routes
        $routes->group('', ['filter' => 'permission:event.venues.read'], function ($routes): void {
            $routes->get('venues', 'VenueController::index');
            $routes->get('venues/(:num)', 'VenueController::show/$1');
        });
        $routes->group('', ['filter' => 'permission:event.venues.write'], function ($routes): void {
            $routes->post('venues', 'VenueController::create');
            $routes->put('venues/(:num)', 'VenueController::update/$1');
        });
        $routes->group('', ['filter' => 'permission:event.venues.delete'], function ($routes): void {
            $routes->delete('venues/(:num)', 'VenueController::delete/$1');
        });
        // Occurrence Routes
        $routes->group('', ['filter' => 'permission:event.occurrences.read'], function ($routes): void {
            $routes->get('occurrences', 'OccurrenceController::index');
            $routes->get('occurrences/(:num)', 'OccurrenceController::show/$1');
        });
        $routes->group('', ['filter' => 'permission:event.occurrences.write'], function ($routes): void {
            $routes->post('occurrences', 'OccurrenceController::create');
            $routes->put('occurrences/(:num)', 'OccurrenceController::update/$1');
        });
        $routes->group('', ['filter' => 'permission:event.occurrences.delete'], function ($routes): void {
            $routes->delete('occurrences/(:num)', 'OccurrenceController::delete/$1');
        });
        // Event Reference Routes
        $routes->group('', ['filter' => 'permission:event.event-references.read'], function ($routes): void {
            $routes->get('event-references', 'EventReferenceController::index');
            $routes->get('event-references/(:num)', 'EventReferenceController::show/$1');
        });
        $routes->group('', ['filter' => 'permission:event.event-references.write'], function ($routes): void {
            $routes->post('event-references', 'EventReferenceController::create');
            $routes->put('event-references/(:num)', 'EventReferenceController::update/$1');
        });
        $routes->group('', ['filter' => 'permission:event.event-references.delete'], function ($routes): void {
            $routes->delete('event-references/(:num)', 'EventReferenceController::delete/$1');
        });
        // TicketType Routes
        $routes->group('', ['filter' => 'permission:event.ticket-types.read'], function ($routes): void {
            $routes->get('ticket-types', 'TicketTypeController::index');
            $routes->get('ticket-types/(:num)', 'TicketTypeController::show/$1');
        });
        $routes->group('', ['filter' => 'permission:event.ticket-types.write'], function ($routes): void {
            $routes->post('ticket-types', 'TicketTypeController::create');
            $routes->put('ticket-types/(:num)', 'TicketTypeController::update/$1');
        });
        $routes->group('', ['filter' => 'permission:event.ticket-types.delete'], function ($routes): void {
            $routes->delete('ticket-types/(:num)', 'TicketTypeController::delete/$1');
        });
        // Booking Routes
        $routes->group('', ['filter' => 'permission:event.bookings.read'], function ($routes): void {
            $routes->get('bookings', 'BookingController::index');
            $routes->get('bookings/(:num)', 'BookingController::show/$1');
        });
        $routes->group('', ['filter' => 'permission:event.bookings.write'], function ($routes): void {
            $routes->post('bookings', 'BookingController::create');
            $routes->put('bookings/(:num)', 'BookingController::update/$1');
        });
        $routes->group('', ['filter' => 'permission:event.bookings.delete'], function ($routes): void {
            $routes->delete('bookings/(:num)', 'BookingController::delete/$1');
        });
        // Ticket Routes
        $routes->group('', ['filter' => 'permission:event.tickets.read'], function ($routes): void {
            $routes->get('tickets', 'TicketController::index');
            $routes->get('tickets/(:num)', 'TicketController::show/$1');
        });
        $routes->group('', ['filter' => 'permission:event.tickets.write'], function ($routes): void {
            $routes->post('tickets', 'TicketController::create');
            $routes->put('tickets/(:num)', 'TicketController::update/$1');
        });
        $routes->group('', ['filter' => 'permission:event.tickets.delete'], function ($routes): void {
            $routes->delete('tickets/(:num)', 'TicketController::delete/$1');
        });
    });
});
