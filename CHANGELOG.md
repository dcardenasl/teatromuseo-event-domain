# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **Localized content** — Events, Venues and TicketTypes now expose `translations`/`localized`
  fields resolved from `Accept-Language`, additive to the existing legacy fields.
- **External reference deduplication** — `event_references` links to other domains (e.g. CMS
  entries) are now idempotent: re-submitting the same link returns the existing record instead of
  duplicating or erroring.

### Fixed

- **`EventModel`** — creating an event no longer fails validation before the model's own
  `beforeInsert` hook can generate its UUID.
- **`EventReferenceModel`** — `metadata` is now cast as nullable JSON, fixing a write error when
  storing a reference without metadata.

### Changed

- **Controllers** (`EventReferenceController`, `OccurrenceController`, `VenueController`) — removed
  dead, mismatched permission checks; authorization is enforced solely by route-level filters.
