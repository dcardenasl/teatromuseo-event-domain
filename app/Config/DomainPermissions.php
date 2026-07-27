<?php

declare(strict_types=1);

namespace Config;

/**
 * Source of truth for the permissions exposed by this domain app.
 *
 * Add an entry here, then run:
 *
 *     php spark domain:sync-permissions
 *
 * to register them in the hub. The command is idempotent — pre-existing codes
 * are left untouched.
 *
 * Permission codes use `.` as separator (NOT `:`) because CodeIgniter splits
 * filter arguments on `:` (`permission:foo:bar` would be parsed as filter=foo,
 * arg=[bar], silently dropping the rest).
 */
class DomainPermissions
{
    /**
     * @var list<array{code: string, resource: string, action: string, description?: string}>
     */
    public const PERMISSIONS = [
        ['code' => 'events.read', 'resource' => 'events', 'action' => 'read', 'description' => 'Read Events'],
        ['code' => 'events.write', 'resource' => 'events', 'action' => 'write', 'description' => 'Create or update Events'],
        ['code' => 'events.delete', 'resource' => 'events', 'action' => 'delete', 'description' => 'Delete Events'],
        ['code' => 'ticket-types.read', 'resource' => 'ticket-types', 'action' => 'read', 'description' => 'Read TicketTypes'],
        ['code' => 'ticket-types.write', 'resource' => 'ticket-types', 'action' => 'write', 'description' => 'Create or update TicketTypes'],
        ['code' => 'ticket-types.delete', 'resource' => 'ticket-types', 'action' => 'delete', 'description' => 'Delete TicketTypes'],
        ['code' => 'bookings.read', 'resource' => 'bookings', 'action' => 'read', 'description' => 'Read Bookings'],
        ['code' => 'bookings.write', 'resource' => 'bookings', 'action' => 'write', 'description' => 'Create or update Bookings'],
        ['code' => 'bookings.delete', 'resource' => 'bookings', 'action' => 'delete', 'description' => 'Delete Bookings'],
        ['code' => 'tickets.read', 'resource' => 'tickets', 'action' => 'read', 'description' => 'Read Tickets'],
        ['code' => 'tickets.write', 'resource' => 'tickets', 'action' => 'write', 'description' => 'Create or update Tickets'],
        ['code' => 'tickets.delete', 'resource' => 'tickets', 'action' => 'delete', 'description' => 'Delete Tickets'],
    ];
}
