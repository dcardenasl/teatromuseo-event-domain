<?php

declare(strict_types=1);

/** @var \CodeIgniter\Router\RouteCollection $routes */
$routes->group('public/events', ['namespace' => '\App\Controllers\Api\V1\Events', 'filter' => ['webappkey', 'throttle']], function ($routes): void {
    $routes->get('', 'PublicEventController::index');
    $routes->get('(:segment)', 'PublicEventController::show/$1');
});
