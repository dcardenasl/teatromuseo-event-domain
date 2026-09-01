# CLAUDE.md

Guidance for Claude Code when working in this repository.

## ⚡ Workflow — read this first

**Before touching any code, read `TASKS.md` in this directory.**

1. Take the first task from `## 🟡 Próximo`
2. Move it to `## 🔴 En progreso`
3. Work exclusively on that task — if anything is unclear, ask before implementing
4. When done: move it to `## ✅ Completadas` with one line of notes (what you did and why)
5. Never work on tasks not defined in TASKS.md without explicit confirmation

For cross-repo context, read `../TASKS.md`.

---

## What this is

`teatromuseo-event-domain` is a CodeIgniter 4 **domain app**. It owns its own
business logic and database tables, but **delegates auth and IAM to the Teatro Museo API**
— the central `teatromuseo-api` application that stores users, applications,
roles and permissions.

The split:

```
Browser/SPA → Domain App (here)        → Database (this app's tables)
                ↓ JWT validation
              Teatro Museo API        → Database (users, roles, perms)
                ↑ Service token (M2M)
              Domain App  ─────────────┘
```

**Boundaries:**

- Domain app **never issues JWTs**. The hub does.
- Domain app **validates JWTs** by calling `POST /api/v1/auth/introspect` on the hub.
- Domain app **registers its permissions** in the hub via `POST /api/v1/iam/self-permissions`
  using its own X-App-Key (`hub.apiKey`). No superadmin JWT required for the primary registration.
  `--admin-token` is only needed when `--mirror-to-self` (**deprecated**) or `--assign-to-role`
  is also set.
- Domain app **does not store users**. There is no `users` table here.

## Essential commands

```bash
# Dev server (default port 8193; Teatro Museo API runs on :8180)
# IMPORTANT: CI4 spark serve requires a SPACE before the port — equals sign is silently ignored:
#   php spark serve --port 8193   ✅
#   php spark serve --port=8193   ❌ (starts on :8080 without warning, collides with the API)
php spark serve --port 8193

# Tests
vendor/bin/phpunit
vendor/bin/phpunit tests/Unit
vendor/bin/phpunit tests/Integration
vendor/bin/phpunit tests/Feature

# Quality gates
composer quality          # phpstan + cs-check + phpunit
composer cs-fix           # auto-fix style

# Database
php spark migrate         # idempotency_keys, audit_logs, request_logs, metrics, jobs

# Hub permission sync (idempotent — safe to rerun). Uses this app's own X-App-Key,
# no superadmin JWT needed for the primary registration.
php spark domain:sync-permissions

# CRUD scaffolding (always use the shell wrapper)
bash vendor/bin/make-crud.sh ResourceName DomainName 'field1:type,field2:type' yes
```

## Architecture cheat sheet

The DTO-first layered pattern is identical to teatromuseo-api:

```
Controller → [RequestDTO] → Service → Model → Entity → [ResponseDTO]
```

Base classes live in `dcardenasl/ci4-api-core` (path repo `../ci4-api-core`,
declared in `composer.json` and consumed under `vendor/dcardenasl/ci4-api-core/`).
Generated and hand-written code imports them directly from the package namespace:

- `dcardenasl\Ci4ApiCore\Http\ApiController` — declarative `handleRequest()` orchestration
- `dcardenasl\Ci4ApiCore\Services\BaseCrudService` — pure, transactional service layer
- `dcardenasl\Ci4ApiCore\Dto\BaseRequestDTO` — auto-validating DTOs
- `dcardenasl\Ci4ApiCore\Models\BaseAuditableModel` — local audit logging via `Auditable` trait

What's **different** here:

- `App\Filters\DomainAuthFilter` (alias `domainauth`) replaces `JwtAuthFilter`. It
  calls `HubClient::introspect()` and injects `(uid, permissions[])` into
  `ApiRequest::setAuthContext()` and `ContextHolder` so `PermissionFilter` works
  unchanged.
- `App\Libraries\Hub\HubClient` is the only place that talks to the hub. It
  handles introspection caching (TTL configurable via `hub.introspectCacheTtl`)
  and service-token caching (refreshed `serviceTokenSafetyMargin` seconds before
  expiry).
- `App\Commands\SyncPermissions` (`php spark domain:sync-permissions`) registers
  every permission in `Config\DomainPermissions::PERMISSIONS` using this domain's
  own X-App-Key via `POST /api/v1/iam/self-permissions` — no superadmin JWT needed
  for the primary registration. `--mirror-to-self --admin-token=<jwt>` additionally
  registers the permissions under hub app `self` (application_id=1); this flag is
  **[DEPRECATED]** since the hub now resolves permissions across every registered
  application via `resolveAll()`.
- `Config\Scaffolding` overrides `protectedRouteFilters` to
  `['domainauth', 'permission:items.read', 'throttle']` — generated CRUDs are
  protected by `domainauth` automatically.

## Adding a new permission

1. Edit `app/Config/DomainPermissions.php` — append to the `PERMISSIONS` array.
2. Run `php spark domain:sync-permissions` — registers in the hub (idempotent).
3. In the hub admin panel, attach the new permission to the role(s) that should
   carry it.
4. Use the code in your filter argument: `permission:items.archive`.

## Adding a new CRUD module

```bash
bash vendor/bin/make-crud.sh Item Example 'name:string:required|searchable,description:text' yes
php spark migrate
pkill -f 'spark serve'; php spark serve --port 8193 &
```

The generator emits routes already wrapped in `domainauth + permission:items.read + throttle`
— no manual filter wiring needed. Update the route filters per HTTP verb if your
module needs distinct read/write codes.

## Required environment variables

| Variable | Purpose |
|---|---|
| `hub.url` | Base URL of the Teatro Museo API (e.g. `http://localhost:8180`) |
| `hub.apiKey` | X-App-Key bound to this domain app's `applications` row in the hub |
| `hub.appCode` | Application code as registered in the hub |
| `hub.introspectCacheTtl` | (optional) TTL in seconds for cached introspect responses, default 60 |
| `hub.adminToken` | (optional) Superadmin JWT. Only needed when running `domain:sync-permissions --mirror-to-self` (**deprecated**) or `--assign-to-role`. |
| `database.default.*` | Domain app's own MySQL connection |
| `encryption.key` | CI4 encryption key (32 bytes after `hex2bin:` decode) |

## Setup prerequisite

`init.sh` runs `php spark domain:sync-permissions` against the hub. The primary
permission registration uses the domain's own X-App-Key (`hub.apiKey`) via
`POST /api/v1/iam/self-permissions` — **no superadmin JWT required** for this step.

The call requires:

1. The hub is running and reachable.
2. An entry in the hub's `applications` table with `code = hub.appCode`.
3. An API key in the hub bound to that application (see `php spark apps:bootstrap`
   on the hub side).
4. `--admin-token` is required only when `--mirror-to-self` or `--assign-to-role`
   is set. The hub gates `POST /api/v1/iam/permissions` (used for the mirror) on
   `iam.superadmin-access`. Obtain via `POST /api/v1/auth/login` as superadmin.

You can re-run `domain:sync-permissions` at any time — it is idempotent.

## Static analysis

PHPStan runs at level 8 with no baseline. Run before pushing:

```bash
composer quality
```

## Common pitfalls

- Public-read Event listings and details are owned by
  `teatromuseo-bff/app/PublicRead/Event/`. If a migration changes a table
  used by that read model, update and verify the BFF in the same session;
  there is no cross-repository CI gate that detects the drift automatically.

- ❌ Issuing JWTs from this app — that's the hub's job, always.
- ❌ Calling `Services::userModel()` or any IAM service — those only exist in the hub.
- ❌ Storing tokens in cookies/localStorage on the client side — use PHP sessions
  (admin/web layer) or pass via Authorization header (SPAs).
- ❌ Hardcoding permission strings — use `DomainPermissions::PERMISSIONS` and
  `domain:sync-permissions` so the hub stays in sync.

## Where to read next

- `../teatromuseo-api/AGENTS.md` — the API hub's patterns + service-token / introspect contracts.
- `vendor/dcardenasl/ci4-api-core/docs/ARCHITECTURE_CONTRACT.md` — DTO-first patterns enforced by the scaffolding engine (or `../ci4-api-core/docs/ARCHITECTURE_CONTRACT.md` while the path repo is symlinked).
