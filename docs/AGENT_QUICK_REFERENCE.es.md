# Referencia rápida para agentes — `teatromuseo-event-domain`

Lee `CLAUDE.md` y `TASKS.md` antes de editar. Este dominio usa el puerto
`8193`, posee la programación/eventos y delega autenticación e IAM al Hub en
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

La sincronización normal usa el `X-App-Key` de este dominio y es idempotente.
`--admin-token` solo se necesita para espejado o asignación opcionales. Reinicia
el servidor después de agregar rutas.

## Reglas

- `DomainAuthFilter`/`domainauth` y `HubClient` gestionan las rutas protegidas;
  nunca emitas JWT ni llames al Hub directamente desde controladores.
- Las rutas públicas `/api/v1/public/events/*` usan `webappkey`, no JWT de
  usuario.
- Mantén traducciones, slugs públicos, ticketing y reservas dentro de
  servicios, no en controladores ni vistas.
- Usa DTOs, servicios/repositorios, constantes de permisos y tests en cada
  cambio. Los permisos usan `.` y no `:`.
- No hagas commit de `.env`, tokens, credenciales ni lógica de negocio en
  vistas.
