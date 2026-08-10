<?php

declare(strict_types=1);

/** @var \CodeIgniter\Router\RouteCollection $routes */
$routes->group('public-read', ['namespace' => '\App\Controllers\Api\V1\Events', 'filter' => ['webappkey', 'throttle', 'correlationid', 'publicTelemetry']], static function ($routes): void {
    $routes->get('(:segment)/events', 'PublicReadController::index/$1');
    $routes->get('(:segment)/events/(:any)', 'PublicReadController::show/$1/$2');
});
