# Agent Quick Reference — `teatromuseo-event-domain`

Read `CLAUDE.md` and `TASKS.md` before editing. This domain runs on port `8193`,
owns event/programming data, and delegates authentication and IAM to the Hub on
`8180`.

```bash
php spark serve --port 8193
php spark migrate
php spark domain:sync-permissions

bash vendor/bin/make-crud.sh ResourceName Events 'field:type:rules,...' yes [route]
php spark module:check ResourceName --domain Events
php spark swagger:generate

composer test:unit
composer test:integration
composer test:feature
composer quality
composer cs-fix
```

The normal permission sync uses this domain's `X-App-Key` and is idempotent.
`--admin-token` is only for optional mirroring or role assignment. Restart the
server after adding routes.

## Rules

- `DomainAuthFilter`/`domainauth` and `HubClient` handle protected requests;
  never issue JWTs or call Hub URLs directly from controllers.
- Public event routes under `/api/v1/public/events/*` use `webappkey`, not a
  user JWT.
- Keep event translations, public slugs, ticketing, and booking rules in
  services, not controllers or views.
- Use DTOs, service/repository layers, permission constants, and tests for all
  behavior changes. Permission codes use `.` rather than `:`.
- Do not commit `.env`, tokens, credentials, or business logic in views.
