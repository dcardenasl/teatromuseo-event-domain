# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

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

- **`Filters` config** — removed the `pagecache` filter (before/after `*`), which was serving
  stale cached responses on public read endpoints instead of reflecting recent writes.
- **`EventModel`** — creating an event no longer fails validation before the model's own
  `beforeInsert` hook can generate its UUID.
- **`EventReferenceModel`** — `metadata` is now cast as nullable JSON, fixing a write error when
  storing a reference without metadata.
- **`EventUpdateRequestDTO`, `TicketUpdateRequestDTO`, `EventReferenceUpdateRequestDTO`, `OccurrenceUpdateRequestDTO`, `TicketTypeUpdateRequestDTO`, `VenueUpdateRequestDTO`, `BookingUpdateRequestDTO`** — update requests can now explicitly clear a nullable field to `null` instead of silently dropping it.

### Changed

- **Controllers** (`EventReferenceController`, `OccurrenceController`, `VenueController`) — removed
  dead, mismatched permission checks; authorization is enforced solely by route-level filters.
