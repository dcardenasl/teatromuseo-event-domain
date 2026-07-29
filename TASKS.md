# TASKS — teatromuseo-event-domain

> Fuente de verdad para trabajo en este repo.
> Historial de completadas: ver `TASKS_ARCHIVE.md`.
> Cross-repo: ver `../TASKS.md`.
> Última actualización: 2026-05-25 (DOM-108 ✅ completado — onboarding desatendido)

---

## 🔴 En progreso

*(vacío)*

---

## 🟡 Próximo

*(vacío)*

---

## ✅ Completadas

### EVT-DOM-003 — Slugs públicos por idioma + endpoint de detalle público (2026-07-28)
- **Qué**: tabla `event_public_slugs` (UNIQUE `(resource_type, locale, slug)` +
  UNIQUE `(resource_type, resource_id, locale)`) con `SlugGenerator` (transliteración vía
  `Normalizer` FORM_D — iconv solo no sirve: en macOS translitera `ó` como `'o`) y
  `PublicSlugStore` (generación estable: el slug NO cambia al editar el título; slug manual
  por locale vía key `slug` en las filas de `translations`, extraído en `EventService` antes
  de que el store de traducciones valide el payload). `RequestLocaleResolver` extraído para
  compartir el parser de Accept-Language entre traducciones y slugs. Backfill de eventos
  existentes + seeder actualizado. Endpoint `GET /api/v1/public/events/{idOrSlug}` (id, uuid
  o slug por locale; solo `published`) documentado en OpenAPI junto al index público que no
  tenía docs. `EventResponseDTO` expone `slug` (locale del request) y `slugs` (mapa completo)
  de forma aditiva.
- **Por qué**: el sitio web resolvía el detalle escaneando hasta 20 páginas × 100 eventos y
  comparando títulos slugificados en memoria — URLs frágiles, colisiones y dependencia del
  Accept-Language del visitante. Con slugs por idioma persistidos el detalle es un solo fetch
  y las URLs son estables y SEO-correctas por locale.
- **Verificado**: `composer quality` ✅ (PHPStan L8, CS-Fixer, 215 tests / 504 assertions;
  swagger-validate pendiente solo del commit de `public/swagger.json`); migrate → rollback →
  re-migrate limpio; backfill generó slugs para los 7 eventos reales preexistentes.

### EVT-DOM-001 — Contenido localizado para eventos, venues y tipos de ticket (2026-07-27)
- **Qué**: `event_translations` (locale-code agnóstico, no depende del catálogo de idiomas del CMS),
  `LocalizedTranslationStore` + `TranslationFieldCatalog` + trait `HasLocalizedTranslations` para
  exponer `translations`/`localized` de forma aditiva en `EventResponseDTO`, `TicketTypeResponseDTO`
  y `VenueResponseDTO` (los campos legacy `title`/`name`/`description` se conservan intactos —
  compatible con `teatromuseo-admin`). Backfill de datos legacy incluido en la propia migración de
  creación de tabla + una migración de backfill separada para venues/ticket_types.
- **Limpieza de Controllers**: `EventReferenceController`, `OccurrenceController` y `VenueController`
  perdieron sus checks manuales de `hasPermission()` (usaban códigos mal formados tipo `venue.read`
  que nunca coincidían con los reales `permission:venues.read` ya aplicados a nivel de ruta) — ahora
  usan `handleRequest()` de forma puramente declarativa.
- **Fix real**: `EventModel::uuid` de `required` a `permit_empty` — CI4 valida antes de correr
  `beforeInsert()` (que genera el UUID), así que con `required` la creación de cualquier Event fallaba
  silenciosamente.
- **Resuelto en la auditoría**: `LocalizedTranslationStore` ya no llama `service('request')`
  internamente. Ahora recibe un `?IncomingRequest` opcional por constructor, resuelto una sola vez
  en el punto de wiring (`EventsDomainServices::localizedTranslationStore()`, la capa correcta para
  tocar HTTP) y verificado con `instanceof` antes de inyectarlo (una `CLIRequest` en contexto CLI
  se traduce a `null` sin excepciones). El Store queda puro y testeable sin mocks globales.
  Se agregaron los tests que faltaban: `testResolvePrefersTheAcceptLanguageHeaderOverTheFallbackLocale`
  y `testResolveFallsBackToTheLegacyLocaleWithoutARequest`.
- **Verificado**: `composer quality` (cs-check + phpstan nivel 8 sin baseline + swagger + arch-drift +
  i18n-check + docs-i18n-check + 194 tests) en verde.

### EVT-DOM-002 — Deduplicación de referencias externas (`event_references`) (2026-07-27)
- **Qué**: `ExternalReferenceCatalog` (vocabulario estable de `source_system`/`source_type`/`relation`
  para que ETL/adapters no inventen strings distintos para la misma relación) + migración
  `AddUniqueExternalReferenceKey` (UNIQUE KEY sobre `event_id, source_system, source_type, source_id,
  relation`) para que un importador pueda re-ejecutar el mismo link sin duplicar.
- **Bug corregido en la auditoría**: `EventReferenceService::store()` no aprovechaba la UNIQUE KEY —
  un reintento real de importación habría lanzado una violación de constraint no capturada (500) en
  vez de comportarse de forma idempotente como promete el comentario de la migración.
  `EventReferenceService::store()` ahora verifica existencia por las 5 columnas antes de insertar y
  devuelve el registro existente si ya existe. Test de integración agregado
  (`EventReferenceServiceTest::testStoreIsIdempotentForTheSameExternalReference`).
- **Nota**: `ExternalReferenceCatalog` en sí sigue sin consumidor real (es un helper para que un
  futuro importador desde `cms-domain` construya el payload correcto) — no es deuda, es una pieza
  de infraestructura a la espera de su cliente.

### DOM-108 — Onboarding desatendido y vinculación de roles (2026-05-25)
- **Qué**: `init.sh` ahora acepta `--assign-to-role=ID|code` y lo pasa a `domain:sync-permissions`. `HubClient` captura `ValidationException` para tratar 422 como éxito idempotente. `init.sh` corre `php spark core:install` automáticamente.
- **Por qué**: (Bulletproof V2) Permitir despliegues 100% automáticos desde el orquestador, vinculando nuevos permisos al rol `superadmin` sin intervención manual. Garantizar que el runtime del core esté listo tras el bootstrap.
- **Verificado**: `php -l` limpio. Scripts probados en flujo de kickstart.

### DOM-107 — Patrón de aggregate extension documentado
- **Qué**: `docs/architecture/EXTENSION_GUIDE.{md,es.md}` ahora documenta cuándo `make:crud` deja de alcanzar y cómo evolucionar el módulo generado hacia un aggregate con custom actions, nested resources, relation sync y response enrichment. `README.md` y `docs/README.md` enlazan explícitamente ese patrón.
- **Por qué**: la auditoría del bootstrap `ci4-catalog` mostró que el problema no era solo generar menos código, sino no tener una guía canónica para el salto desde CRUD plano a aggregate real.
- **Verificado**: documentación enlazada desde los entry points principales del repo (`README.md`, `docs/README.md`) y alineada con el playbook de scaffolding existente.

### DOM-106 — Paridad `boolean_like` con el scaffolder
- **Qué**: `App\Validations\Rules\CustomRules` ahora implementa `boolean_like()` con el mismo contrato esperado por `ci4-api-scaffolding`: acepta bools, `0/1`, y strings `true/false/yes/no/on/off` de forma case-insensitive. Se añadieron los strings de validación en `app/Language/en/Validation.php` y `app/Language/es/Validation.php`.
- **Por qué**: el scaffolder emite `boolean_like` para fields `bool`, pero `teatromuseo-event-domain` no exponía esa regla. Eso rompía CRUDs generados con booleanos y obligaba a parchear DTOs/modelos a mano.
- **Verificado**: `vendor/bin/phpunit tests/Unit/Validations/CustomRulesTest.php --configuration=phpunit.xml --no-coverage --testdox` ✅ (10 tests, 28 assertions).

### BFF-107 — Refactor `HubClient` sobre `AbstractServiceClient`
- **Qué**: `app/Libraries/Hub/HubClient.php` pasó de 220 a 155 líneas extendiendo `dcardenasl\Ci4ApiCore\Http\Client\AbstractServiceClient`. Paths del hub movidos a `Config\Hub::$introspectPath/$serviceTokenPath/$permissionsPath`. `RuntimeException` reemplazado por `ServiceUnavailableException`/`AuthenticationException`/`AuthorizationException` canónicas. `registerPermission()` ahora trata 422 igual que 409 como duplicado idempotente. Heredada gratis: propagación de `X-Request-Id`, retry 1× en 5xx/network, allow-list de headers en `forward()`.
- **Por qué**: eliminar drift entre los dos `HubClient.php` (BFF-102 hizo el mismo refactor en el BFF). Cualquier ajuste futuro a timeout/retry/headers se hace una vez, en el core.
- **Verificado**: `DomainAuthFilter` consume `HubClient::introspect()` que mantuvo su firma (devuelve `IntrospectResult`) — cero cambios necesarios en el filter. `composer quality` limpio en domain (PHPStan L8 + CS-Fixer + 145 tests / 353 assertions). 10 tests nuevos en `HubClientTest` (cache hit, refresh, 5xx con retry, introspect downgrade, registerPermission idempotente, 401/403 → excepciones canónicas).
- **Cross-repo**: ver `../TASKS.md` milestone "ci4-bff-starter v1.1".

---

## ⚪ Backlog

- **[DOM-102]** ADR-001 documentando el hub-domain split (auth delegation, permission ownership, no users table aquí).
- **[DOM-103]** `php spark domain:doctor` — comando diagnóstico que alcanza el hub y reporta status de introspect / service-token / register-permission.

---

## 🏗️ Contratos de arquitectura

- **DTO-First:** todo Controller in/out usa DTOs. Request DTOs extienden `BaseRequestDTO`. Nunca arrays raw.
- **Services puros:** no conocen HTTP. Reciben DTOs, devuelven DTOs o lanzan excepciones de dominio.
- **Controllers delgados:** usar `ApiController::handleRequest()`. Sin lógica de negocio.
- **Separador de permisos:** punto `.` (NO `:`).
- **Hub delegation:** nunca validar JWTs localmente. Siempre `HubClient::introspect()`.
- **No tabla users:** si estás agregando una migración de usuarios, para — esos datos viven en el hub.
- **Rutas por dominio:** `app/Config/Routes/v1/<dominio>.php`.
- **Tests:** todo endpoint nuevo necesita al menos un Feature test (o waiver explícito en TASKS.md).
- **`composer cs-fix` antes de commitear.** No bypasear el pre-commit hook con `--no-verify`.

### 🚧 Technical Debt (Orchestration)
- [x] **Clean .env Management**: Migrate init.sh from appending to .env to using bootstrap_env.php to prevent duplicate keys. ✅ (Verificado en Bulletproof V2)
- [x] **Permission Assignment**: Add --assign-to-role=superadmin option to domain:sync-permissions to automate linking new permissions. ✅ 2026-05-25
