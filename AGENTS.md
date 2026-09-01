# teatromuseo-event-domain — Event Domain Repository Guidelines

> **Responsibility:** Events, Venues, Ticket Types, Bookings, Occurrences, Event References, Localized Slugs.
> **Port:** 8193

## Key Architectural Rules

1. **Auth Delegation:** Never decode JWTs locally. Delegate token introspection to the Hub via `DomainAuthFilter` and `HubClient`.
2. **Permission Format:** Permission codes use dot notation (`event.events.read`, `event.events.write`), matching the permissions registered on the Hub.
3. **Public Endpoints:** Public endpoints (`/api/v1/public/*`) require `WEB_API_KEY` via `X-App-Key` header and rate limiting (`WebAppKeyRequiredFilter`).
4. **DTO-First & ApiController:** Controllers extend `ApiController` and use `handleRequest()`.
5. **Quality Gates:** Run `composer quality` before committing (PHPStan Level 8, CS-Fixer, OpenAPI validation, PHPUnit).
