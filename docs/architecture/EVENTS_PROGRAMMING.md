# Events programming model

This domain owns the operational programming of Teatro Museo. It does not own
editorial pages, museum catalog content, or users.

## Aggregate shape

`Event` is the programming aggregate root. It identifies the activity being
programmed: a function, festival, course, workshop, or another public activity.

`Occurrence` is a concrete scheduled session of an event. Dates, times,
venue, capacity, availability, and operational status belong to occurrences.
This allows one work, festival, course, or workshop to have multiple sessions
without duplicating the event itself.

`Venue` is a reusable operational space. A venue may be referenced by many
occurrences and may be retired without deleting historical occurrences.

`TicketType`, `Booking`, and `Ticket` form the optional access-control slice.
Ticket types will ultimately belong to an occurrence because availability is a
property of a concrete session, not of the general event.

`EventReference` stores links to records owned by another system, such as a
Catalog item, CMS page, or legacy record. These references use
`source_system`, `source_type`, and `source_id`; they never create foreign keys
across domain databases.

## Transition from the initial scaffold

The first `events` migration currently contains `start_time`, `end_time`,
`venue`, `capacity`, and `available_spots`. Those fields are occurrence data.
The transition is:

1. Create `venues` and `occurrences`.
2. Convert every existing event schedule into one occurrence.
3. Move ticket-type ownership from `event_id` to `occurrence_id`.
4. Keep compatibility only while the migration is running.
5. Remove or deprecate the schedule columns from `events` before importing legacy data.

## Generated versus manual code

The CRUD scaffolder is the source of truth for repetitive resources such as
venues, occurrences, and external references. It generates DTOs, services,
controllers, migrations, routes, permissions, OpenAPI documentation, and
tests.

The event aggregate and ticketing operations require manual extensions for
nested operations and invariants: time ordering, venue conflicts, capacity,
availability, booking holds, idempotency, and safe state transitions.

## Cross-domain and content rules

- Event descriptions and labels follow the project's translation strategy.
- Catalog and CMS records are linked by external references, never by database
  foreign keys.
- Public endpoints expose published events and occurrences only.
- Bookings, tickets, audit data, and administrative fields remain private.
