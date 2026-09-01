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
        ['code' => 'event.events.read', 'resource' => 'events', 'action' => 'read', 'description' => 'Read Events'],
        ['code' => 'event.events.write', 'resource' => 'events', 'action' => 'write', 'description' => 'Create or update Events'],
        ['code' => 'event.events.delete', 'resource' => 'events', 'action' => 'delete', 'description' => 'Delete Events'],
        ['code' => 'event.event-types.read', 'resource' => 'event-types', 'action' => 'read', 'description' => 'Read Event Types'],
        ['code' => 'event.event-types.write', 'resource' => 'event-types', 'action' => 'write', 'description' => 'Create or update Event Types'],
        ['code' => 'event.event-types.delete', 'resource' => 'event-types', 'action' => 'delete', 'description' => 'Delete Event Types'],
        ['code' => 'event.ticket-types.read', 'resource' => 'ticket-types', 'action' => 'read', 'description' => 'Read TicketTypes'],
        ['code' => 'event.ticket-types.write', 'resource' => 'ticket-types', 'action' => 'write', 'description' => 'Create or update TicketTypes'],
        ['code' => 'event.ticket-types.delete', 'resource' => 'ticket-types', 'action' => 'delete', 'description' => 'Delete TicketTypes'],
        ['code' => 'event.bookings.read', 'resource' => 'bookings', 'action' => 'read', 'description' => 'Read Bookings'],
        ['code' => 'event.bookings.write', 'resource' => 'bookings', 'action' => 'write', 'description' => 'Create or update Bookings'],
        ['code' => 'event.bookings.delete', 'resource' => 'bookings', 'action' => 'delete', 'description' => 'Delete Bookings'],
        ['code' => 'event.tickets.read', 'resource' => 'tickets', 'action' => 'read', 'description' => 'Read Tickets'],
        ['code' => 'event.tickets.write', 'resource' => 'tickets', 'action' => 'write', 'description' => 'Create or update Tickets'],
        ['code' => 'event.tickets.delete', 'resource' => 'tickets', 'action' => 'delete', 'description' => 'Delete Tickets'],
        ['code' => 'event.venues.read', 'resource' => 'venues', 'action' => 'read', 'description' => 'Read Venues'],
        ['code' => 'event.venues.write', 'resource' => 'venues', 'action' => 'write', 'description' => 'Create or update Venues'],
        ['code' => 'event.venues.delete', 'resource' => 'venues', 'action' => 'delete', 'description' => 'Delete Venues'],
        ['code' => 'event.occurrences.read', 'resource' => 'occurrences', 'action' => 'read', 'description' => 'Read Occurrences'],
        ['code' => 'event.occurrences.write', 'resource' => 'occurrences', 'action' => 'write', 'description' => 'Create or update Occurrences'],
        ['code' => 'event.occurrences.delete', 'resource' => 'occurrences', 'action' => 'delete', 'description' => 'Delete Occurrences'],
        ['code' => 'event.event-references.read', 'resource' => 'event-references', 'action' => 'read', 'description' => 'Read Event References'],
        ['code' => 'event.event-references.write', 'resource' => 'event-references', 'action' => 'write', 'description' => 'Create or update Event References'],
        ['code' => 'event.event-references.delete', 'resource' => 'event-references', 'action' => 'delete', 'description' => 'Delete Event References'],
    ];
}
