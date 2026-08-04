# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **Event type catalog and public API** — added administrable event types and localized public
  event-type responses for the public programming experience.
- **Upcoming-first public programming** — public event listings now order upcoming events first
  and past events in reverse chronological order.

- **Localized public slug store & generator** — introduced `PublicSlugStore`, `SlugGenerator`, `RequestLocaleResolver`, `HasPublicSlugs` trait, and migrations `2026-07-28-040000_CreateEventPublicSlugsTable` / `2026-07-28-040500_BackfillEventPublicSlugs` for localized event slug resolution and historical URL tracking.
- **Public Event API (`/api/v1/public/events`)** — created `PublicEventController` gated by `WebAppKeyRequiredFilter` for querying public events by localized slug or date range, documented in OpenAPI/Swagger (`PublicEventEndpoints`).
- **Localized content** — Events, Venues and TicketTypes now expose `translations`/`localized`
  fields resolved from `Accept-Language`, additive to the existing legacy fields.
- **External reference deduplication** — `event_references` links to other domains (e.g. CMS
  entries) are now idempotent: re-submitting the same link returns the existing record instead of
  duplicating or erroring.
- **`cover_file_id` / `gallery_file_ids` on events** — events can now carry a cover image and
  gallery, resolved to Hub file metadata (`cover_image`/`gallery_images`) on
  `/api/v1/public/events*` via `HubClient::resolvePublicFileMeta()`.
- **`internal/files/*` endpoints** — `HubSignatureFilter` + `InternalFileController` let the Hub
  check whether a file is referenced by an event before deleting it, and invalidate this domain's
  cached file metadata after a replace, via HMAC-signed requests.
- **Admin sidebar grouping** — Events' 7 flat sidebar items are now grouped into "Scheduling"
  (Event, Venue, Occurrence, EventReference) and "Ticketing" (TicketType, Booking, Ticket)
  sections in `template.json`.

### Fixed

- **Localized event type slugs** — event type slugs are backfilled deterministically and public
  responses preserve their canonical localized values.
- **Deterministic localization fallback** — slug transliteration now remains stable for accented
  and non-ASCII event content.
- **Superadmin authorization** — superadmins can bypass domain permission assignments where the
  central authorization contract grants that role globally.

- **`Filters` config** — removed the `pagecache` filter (before/after `*`), which was serving
  stale cached responses on public read endpoints instead of reflecting recent writes.
- **`EventModel`** — creating an event no longer fails validation before the model's own
  `beforeInsert` hook can generate its UUID.
- **`EventReferenceModel`** — `metadata` is now cast as nullable JSON, fixing a write error when
  storing a reference without metadata.
- **`EventUpdateRequestDTO`, `TicketUpdateRequestDTO`, `EventReferenceUpdateRequestDTO`, `OccurrenceUpdateRequestDTO`, `TicketTypeUpdateRequestDTO`, `VenueUpdateRequestDTO`, `BookingUpdateRequestDTO`** — update requests can now explicitly clear a nullable field to `null` instead of silently dropping it.

### Changed

- **Event type search performance** — added a full-text index for event type search.
- **Seed baseline** — removed demo event data and its seeded rows from the domain baseline.

- **Controllers** (`EventReferenceController`, `OccurrenceController`, `VenueController`) — removed
  dead, mismatched permission checks; authorization is enforced solely by route-level filters.
- **`DomainPermissions::PERMISSIONS` / `events.php` routes / `template.json`** — renamed permission
  codes to the `event.*` namespace (e.g. `events.read` → `event.events.read`) to avoid colliding
  with other domains' permission codes.
- **`domain:sync-permissions`** — primary permission registration now uses the hub's
  `POST /api/v1/iam/self-permissions` endpoint via this domain's own X-App-Key; a superadmin JWT
  is no longer required except when `--mirror-to-self` (now deprecated) or `--assign-to-role` is
  used.
