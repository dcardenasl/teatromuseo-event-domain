<?php

declare(strict_types=1);

/** @var \CodeIgniter\Router\RouteCollection $routes */
$routes->group('public/events', ['namespace' => '\App\Controllers\Api\V1\Events', 'filter' => ['webappkey', 'throttle']], function ($routes): void {
    $routes->get('types', 'PublicEventController::types');
    // `GET public/events` (bare listing) removed 2026-08-13 — its
    // controller method (index()) called the now-deleted
    // EventService::indexPublicCartelera() (unbounded pagination + PHP-side
    // usort()) and did an HTTP-per-item N+1 against the Hub for media.
    // teatromuseo-web migrated its cartelera listing to
    // public-read/{lang}/events; confirmed zero remaining callers across
    // teatromuseo-web/bff/admin/totem. See
    // docs/audits/2026-08-12-auditoria-parte2-rendimiento-listados-publicos.md §2.5/§2.F.
    $routes->get('(:segment)', 'PublicEventController::show/$1');
});
