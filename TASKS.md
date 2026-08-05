# TASKS — teatromuseo-event-domain

> Fuente de verdad para trabajo en este repo.
> Historial de completadas: ver `TASKS_ARCHIVE.md`.
> Cross-repo: ver `../TASKS.md`.
> Última actualización: 2026-08-05 (SEC-06 + CFG-04 + CFG-02 + CFG-07 + CFG-08 completadas)

---

## 🔴 En progreso

*(vacío)*

---

## 🟡 Próximo

> Saneamiento arquitectónico — auditoría del 2026-08-05.
> **Contexto, evidencia y rutas exactas:** [`../docs/plan/2026-08-05-saneamiento-arquitectonico.md`](../docs/plan/2026-08-05-saneamiento-arquitectonico.md)
> Orden y dependencias cross-repo: [`../TASKS.md`](../TASKS.md)
>
> La auditoría partió de un CI que nunca se ejecutaba en `dev` y de un baseline de PHPStan de
> 125 errores ocultos. SEC-06, CFG-04, CFG-02, CFG-07 y CFG-08 ya fueron resueltas en esta sesión;
> las tareas siguientes permanecen pendientes.

### Fase 1 — Seguridad

### Fase 2 — Configuración y CI

### Fase 3 — Extracción a `ci4-api-core`

- [ ] **CORE-01 — Extraer el stack de localización.** Este repo es la **implementación de referencia** de la
  que se portó la de catalog: ~830 líneas hoy forkeadas.
  `RequestLocaleResolver.php` y `SlugGenerator.php` son byte-idénticos entre ambos;
  `LocalizedTranslationStore.php` y `PublicSlugStore.php` difieren en 3 líneas.
  En las dos divergencias funcionales, **la versión de este repo es la correcta**: el respaldo
  `?: trim((string) ($entity->slug ?? ''))` de `HasPublicSlugs.php:109-110,131-132`
  (catalog lo perdió → `SEC-05`), y la ausencia de `$data['id'] = $id;` en `beforeUpdate()`.
- [ ] **CORE-02 — Consolidar filtros y boilerplate.** Aportar la versión de
  `app/Filters/PermissionFilter.php:46-52` (la única que concede paso a `iam.superadmin-access`,
  con su justificación en comentario) y el `onlyEntities()` de `AuditLogModel` que api y cms nunca
  recibieron.
- [ ] **CORE-03 — `app/Config/Api.php` es una copia verbatim de 148 líneas** de la que publica
  `ci4-api-core`, arrastrando toda la configuración JWT en una app que no puede firmar ni verificar
  un JWT. Extender la base del paquete como ya hace el hub.
- [ ] **CORE-06 — Convención de permisos.** Hoy `event.<kebab-plural>.<read|write|delete>`
  (`event.event-references.write`), incompatible con cms y catalog. ⚠️ Ventana de mantenimiento.

### Fase 4 — Coherencia de capas

- [ ] **LAYER-01 — `Controllers/Api/V1/Events/PublicEventController.php` rompe cuatro reglas a la
  vez:** (1) **muta el superglobal de la petición** para inyectar filtros y un `sort` por defecto
  (l.42-56); (2) **llama al cliente del hub desde el controlador** (l.101,
  `Services::hubClient()`); (3) lleva un `resolveMediaFields()` privado de ~55 líneas que difiere
  de la copia de catalog **solo por el nombre de la variable**; (4) `show()` recibe `(array $dto, ...)`
  con `$dto` sin usar (l.34).
- [ ] **LAYER-03 — `Services/Events/BookingService.php`** accede al builder crudo en l.90-102 y
  l.204-222 — **el mismo bloque duplicado dentro de la misma clase** (locking de `ticket_types`,
  `occurrences`, `events`). Igual `Services/Events/FileUsageService.php:41`.
- [ ] **LAYER-04 — Falta `ControllerModelDependencyConventionsTest`**, que sí existe en cms y
  catalog.
- [ ] **LAYER-06 — Esquema deprecado todavía en el contrato público.**
  `2026-07-27-022000_MakeLegacyEventScheduleNullable.php` anuló `events.start_time`, `end_time`,
  `venue`, `capacity` y `available_spots`, superados por las tablas `occurrences` y `venues`.
  Los cinco **siguen** en `EventModel::$allowedFields/$filterableFields/$sortableFields/$searchableFields`
  (l.23-32) y en `EventResponseDTO` (l.46-55, 79-83). Retirarlos, con nota de versión en el swagger.

### Fase 5 — Migraciones y semillas

- [ ] **MIG-01 — Cadena de parcheo:** `BackfillEventTypeLocalizedSlugs` →
  `EnsureEventTypeLocalizedCatalog` → `RepairEventTypeSlugFormat`, las tres en 60 minutos. El
  docblock de la tercera lo confiesa (*"Rewrites event-type slugs produced by the old iconv-only
  fallback"*). Consolidar.
- [ ] **MIG-02 — Las tablas de localización no tienen claves foráneas** hacia sus padres
  (`event_translations`, `event_public_slugs`). El `status` de `events`/`bookings`/`occurrences`/
  `tickets` usa columnas planas con `addKey('status')`, mientras cms usa `ENUM` crudo y catalog
  ninguno de los dos — tres enfoques para el mismo problema.
- [ ] **MIG-03 — Cero seeders.** Esta app no tiene ningún camino de bootstrap, a diferencia de api
  y cms. Decidir si necesita uno (mínimo: venues y ticket types base).
- [ ] **HYG-01 — Purgar y rotar `writable/debugbar` (411 MB).**

### Fase 6 — Limpieza y docs

- [ ] **DEAD-02 — 13 directorios vacíos** que dejó un módulo demo ya borrado: `app/Support/`,
  `Documentation/{Demo,Example}/`, `Services/{Demo,Core}/`, `Interfaces/{Demo,System}/`,
  `DTO/{Request,Response}/{Demo,Example}/`, `Libraries/{Security,Queue/Jobs}/`.
  Corregir también `declare (strict_types=1);` (con espacio) en
  `app/Config/Routes/v1/events.php:3` — php-cs-fixer no pasa por los archivos de ruta generados.
- [ ] **DOC-01 — Crear el `AGENTS.md` que falta** en este repo (existe en bff, catalog, cms y
  tótem; el `AGENTS.md` raíz ni siquiera lista a esta app).

---

## ✅ Completadas

### CFG-08 — Tooling y hook de publicación alineados (2026-08-05)
- PHPStan actualizado a 2.2.8 en `composer.json` y `composer.lock`; añadido `pre-push` no
  bloqueante e integrado en la instalación automática de hooks; matriz de CI verificada para
  PHP 8.2–8.3; eliminado el backup obsoleto `.env.bak.1785113747`.
- **Verificado**: `composer quality` ✅ — PHPStan nivel 8 sin errores, OpenAPI válido,
  arquitectura/i18n correctos, 226 pruebas, 570 aserciones y 1 skip preexistente.

### CFG-07 — Contenedor reproducible y sin descripción heredada (2026-08-05)
- Se añadió `docker/entrypoint.sh` al repositorio para esperar la base de datos y ejecutar las
  migraciones antes de Apache; el `Dockerfile` aplica actualizaciones de seguridad y describe
  correctamente el dominio de eventos, sin afirmar autenticación JWT.

### CFG-02 — Variables de entorno documentadas (2026-08-05)
- `.env.example` ahora documenta las 18 claves leídas por event-domain, con defaults seguros,
  secretos vacíos y alias compatibilidad claramente marcados.

### SEC-06 + CFG-04 — CI en `dev` y PHPStan sin baseline (2026-08-05)
- **SEC-06**: el workflow de CI ahora se ejecuta en `push` sobre `main` y `dev`.
- **CFG-04**: se incorporaron `app/DTO`, `app/Repositories` y `app/Commands` al análisis,
  se retiró el baseline de 745 líneas y se corrigieron los errores reales descubiertos. El
  archivo `phpstan-baseline.neon` queda con `ignoreErrors: []` y no se añadieron supresiones.
- **Verificado**: `composer quality` ✅ — PHPStan nivel 8 sin errores, OpenAPI válido,
  arquitectura/i18n correctos, 226 pruebas, 570 aserciones y 1 skip preexistente.

### EVT-DOM-008 — Eliminar eventos de ejemplo mezclados con los reales (2026-08-02)
- **Qué**: David notó que la Cartelera mezclaba eventos reales (migrados desde la BD legacy de
  teatromuseo.cl) con eventos que "parecían un mockup" — pidió limpiar y dejar solo lo que viene
  de la legacy, y extender la limpieza a los seeders de ejemplo en cms-domain/event-domain/
  catalog-domain. Confirmado con SQL directo: `TeatroMuseoEventSeeder` ("Seeds representative
  published events for local development") creaba 13 eventos falsos (ids 1-13, `uuid` con
  patrón `evt-XXX`: "Festival de Luz", "Actividad Especial", etc.), mezclados con los 368
  eventos reales (ids 21+, creados por `legacy:apply` y respaldados en
  `legacy_migration_map` del hub). `init.sh` corría este seeder en cada instalación nueva.
- **Fix**: los 13 eventos falsos borrados vía `DELETE /events/events/{id}` (soft-delete, cero
  filas huérfanas — confirmado 0 occurrences/event_references/ticket_types apuntando a esos
  ids). `TeatroMuseoEventSeeder.php` eliminado del todo; su llamada en `init.sh` removida.
- **Verificado**: `composer quality` ✅ (220 tests, 1 skip preexistente no relacionado,
  PHPStan sin errores). `events` en vivo: 368/368 sin `deleted_at`, todos con respaldo en
  `legacy_migration_map`. Cartelera pública verificada visualmente — ya no aparece ningún
  evento con título genérico de ejemplo.

### EVT-DOM-007 — Orden de la Cartelera: próximos primero, luego histórico descendente (2026-08-02)
- **Qué**: David pidió que la Cartelera pública muestre lo próximo en el tiempo primero (fecha
  más cercana a hoy hacia adelante), y luego el histórico desde la fecha actual hacia atrás
  (más reciente primero) — el orden natural de una cartelera real. El `sort` genérico del
  scaffolding solo produce una única dirección (`start_time` ASC o DESC), no puede expresar un
  corte en dos direcciones. Nuevo `EventService::indexPublicCartelera()`: recorre todas las
  filas que matchean los criterios existentes (filtro/búsqueda reutilizados sin duplicar lógica)
  vía el `paginateCriteria()` normal en lotes de 100 (acotado por el volumen real de este
  dominio — la programación de un solo teatro, no una tabla de alto volumen), reordena en PHP
  con un comparador (futuro: ascendente; pasado: descendente) y pagina el resultado ya
  reordenado. Cableado únicamente en `PublicEventController::index()` — el CRUD admin sigue
  usando `index()` genérico sin cambios, sin riesgo de romper un `sort` explícito que un admin
  pida.
- **Por qué se necesitó un método nuevo en vez de tocar `sort`**: reordenar solo dentro de una
  página ya paginada por SQL no sirve — los eventos próximos son una fracción pequeña de la
  tabla completa (~380 filas, mayoría histórica desde 2017), así que quedarían enterrados varias
  páginas más adelante en el orden ASC/DESC original en vez de aparecer primero.
- **Verificado**: nuevo test `testIndexOrdersUpcomingFirstThenMostRecentPast` (5 eventos
  sembrados en fechas relativas a "ahora", confirma el orden exacto). `composer quality` ✅
  (220/220 tests, 1 skip preexistente no relacionado, PHPStan sin errores). Verificado en vivo:
  `GET /api/v1/public/events` real devuelve "Festival de Luz" (2026-08-03, la próxima función)
  primero, y la última página termina en el evento más antiguo (2017-03-24); confirmado también
  visualmente en `http://localhost:8184/es/cartelera`.

### EVT-DOM-006 — Fix "no se puede limpiar un campo nullable vía update" en las 7 *UpdateRequestDTO (2026-07-30)
- **Qué**: `EventUpdateRequestDTO`, `TicketUpdateRequestDTO`, `EventReferenceUpdateRequestDTO`,
  `OccurrenceUpdateRequestDTO`, `TicketTypeUpdateRequestDTO`, `VenueUpdateRequestDTO`,
  `BookingUpdateRequestDTO` — todas usaban `array_filter($v !== null)` en `toArray()`, que
  descartaba cualquier campo enviado como `null`, haciendo imposible limpiar explícitamente un
  campo nullable (ej. quitar `cover_file_id`/`venue`/`checked_in_at`). Corregido con ternario de
  una línea por propiedad + `array_key_exists()` + acumulador `$mappedFields`, decidiendo caso por
  caso vía `DESCRIBE` real qué columnas son NOT NULL (nunca aceptan null explícito, tratado igual
  que omitido) vs nullable (null explícito limpia la columna).
- **Por qué**: encontrado durante EVT-DOM-004/005 al confirmar que el botón "Quitar" del picker de
  portada nunca funcionaba — resultó ser un patrón roto en TODA la familia de Update DTOs del
  monorepo (23 archivos en 4 repos), no solo en `cover_file_id`.
- **Verificado**: end-to-end real — `PUT /events/events/1` con `{"venue": null}` → confirmado
  `venue IS NULL` en BD vía SQL directo; `{"title": null}` en el mismo evento NO lo limpia (NOT
  NULL protegido correctamente). `composer quality` ✅ (215/215 tests, PHPStan sin errores).

### EVT-DOM-005 — Endpoints internal/files/* para el Hub (usage-check + invalidate-cache) (2026-07-30)
- **Qué**: `App\Filters\HubSignatureFilter` (alias `hubsignature`) verifica llamadas HMAC del
  Hub (`hub.internalSecret`/env `HUB_INTERNAL_SECRET`, fail-closed). Nuevo
  `App\Services\Events\FileUsageService::getUsagesByHubFileId()` — prefiltra por SQL
  (`cover_file_id` o `LIKE` sobre `gallery_file_ids`) y verifica membresía CSV exacta en PHP
  para evitar falsos positivos por substring. `InternalFileController::usage()`/
  `invalidateCache()` bajo `internal/files/*`, extendiendo `\CodeIgniter\Controller` (no
  `ApiController`) — excepción documentada en `ControllerDtoRequestContractsTest`.
- **Por qué**: el Hub no veía usages de `events.cover_file_id/gallery_file_ids` antes de borrar
  un archivo, y `HubClient::invalidateFileMetaCache()` era dead code.
- **Verificado**: end-to-end real contra el Hub — archivo subido, asignado como cover de un
  evento real, `DELETE /files/{id}` → 409 correcto; `replace()` del archivo reflejado sin TTL
  en `/api/v1/public/events/{id}`; datos de prueba limpiados. `composer quality` ✅ (215/215).
- **Hallazgo colateral, no corregido**: `EventUpdateRequestDTO::toArray()` usa
  `array_filter($v !== null)`, así que enviar `cover_file_id: null` para limpiar la portada se
  descarta en silencio — el botón "Quitar" del picker nunca ha funcionado para events (ni para
  collection_items en catalog-domain, mismo patrón). Reportado, no arreglado — cambiar la
  semántica de PATCH afecta todos los campos nullable del DTO, no solo cover_file_id.

### EVT-DOM-004 — cover_file_id/gallery_file_ids en events + resolución pública de imagen (2026-07-30)
- **Qué**: columna `cover_file_id` (BIGINT unsigned nullable) + `gallery_file_ids` (TEXT nullable) en
  `events` vía migración; expuestos en `EventModel`, `EventEntity`, `EventCreateRequestDTO`,
  `EventUpdateRequestDTO` y `EventResponseDTO`. `PublicEventController::resolveMediaFields()`
  (portado 1:1 desde `teatromuseo-catalog-domain`'s `PublicCollectionItemController`) resuelve
  `cover_file_id`/`gallery_file_ids` vía `HubClient::resolvePublicFileMeta()` y expone
  `cover_image`/`gallery_images` en `index()` y `show()` de `/api/v1/public/events*`. El método
  `resolvePublicFileMeta()` no existía en el `HubClient` local de este repo (sí en catalog-domain) —
  se portó junto con `invalidateFileMetaCache()`.
- **Por qué**: `/es/cartelera` en el sitio público nunca mostraba imágenes de portada de eventos
  porque el modelo de datos de events nunca tuvo campo de imagen (a diferencia de catalog-domain,
  que ya resolvía `cover_file_id` contra el Hub). El código del web público ya buscaba
  `cover_image`/`featured_image` correctamente — el gap estaba enteramente en este repo.
- **Verificado**: `composer phpstan` ✅, `composer cs-check` ✅, 215/215 tests ✅; migración
  corrida en MySQL local; `curl /api/v1/public/events` confirmado devolviendo `cover_image`/
  `gallery_images` (null hasta que se cargue una imagen real vía admin). Swagger regenerado
  (`public/swagger.json` pendiente de commit — `swagger-validate` marcará diff hasta entonces).

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
