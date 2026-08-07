# TASKS — teatromuseo-event-domain

> Fuente de verdad para trabajo en este repo.
> Historial de completadas: ver `TASKS_ARCHIVE.md`.
> Cross-repo: ver `../TASKS.md`.
> Última actualización: 2026-08-07 (LAYER-01 completada)

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

- [x] ~~CORE-01~~ — **completado 2026-08-05.** Ver Completadas. Este repo fue la implementación de
  referencia de la que se portó la del paquete y la de catalog.
- [x] ~~CORE-02~~ — **completado 2026-08-06.** El bypass de superadmin de
  `app/Filters/PermissionFilter.php:46-52` (el único que ya lo tenía) es ahora
  `AbstractPermissionFilter::superAdminBypassCode()` en el paquete, adoptado también por cms y
  catalog — SEC-02 queda unificado en las tres apps. `HasCrudActions` resultó ser código muerto
  (ningún controlador lo usaba) — eliminado, no migrado. El `onlyEntities()` de `AuditLogModel`
  y el drift de esquema en las migraciones de infra **siguen sin reconciliar** — sin base
  compartida en el paquete para eso. Ver Completadas.
- [x] ~~CORE-03~~ — **completado 2026-08-06.** Ver Completadas.
- [ ] **CORE-06 — Convención de permisos.** Hoy `event.<kebab-plural>.<read|write|delete>`
  (`event.event-references.write`), incompatible con cms y catalog.
  **Confirmado fuera de alcance de `ci4-api-core`** — es config local más una migración de datos
  en el hub, no código de paquete. ⚠️ **`domain:sync-permissions` es insert-if-missing, no
  upsert**: renombrar sin migración SQL manual deja huérfanas las filas viejas de `permissions` y
  sus bindings en `role_permissions`. Ventana de mantenimiento — **no tocar sin confirmación
  explícita.**

### Fase 4 — Coherencia de capas

- [x] ~~LAYER-01~~ — **completado 2026-08-07.** Ver Completadas.
- [x] ~~LAYER-03~~ — **completado 2026-08-06.** Ver Completadas.
- [x] ~~LAYER-04~~ — **completado 2026-08-06.** Ver Completadas.
- [x] ~~LAYER-06~~ — **verificado ya resuelto 2026-08-06** (checkbox desactualizado, trabajo
  previo sin commitear). Ver Completadas.

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

### LAYER-01 — `PublicEventController::resolveMediaFields()` extraído a un servicio (2026-08-07)

- **Los 2 sub-problemas que quedaban abiertos (verificados 2026-08-06) están resueltos.**
  `resolveMediaFields()` (~55 líneas: resuelve `cover_file_id`/`gallery_file_ids` a metadata de
  archivo del Hub) salió del controlador hacia `App\Services\Events\EventMediaResolutionService`
  nueva, con `HubClient` inyectado por constructor — el controlador ya no llama
  `Services::hubClient()` en ningún punto (verificado por `grep`, no queda ninguna referencia).
  El servicio se registra en `EventsDomainServices::eventMediaResolutionService()` como clase
  concreta (sin interfaz), el mismo patrón que ya usaba el `FileUsageService` hermano — un
  servicio utilitario pequeño con un solo colaborador, no el patrón CRUD con interfaz de
  `EventService`/`TicketTypeService`. `PublicEventController` ahora resuelve
  `$this->mediaResolutionService` en `resolveDefaultService()` junto al `eventService` existente.
- **Duplicación cross-repo con catalog-domain (sub-problema original #3): verificado que
  catalog-domain NO tiene esta extracción hecha tampoco** —
  `PublicCollectionItemController::resolveMediaFields()` sigue llamando `Services::hubClient()`
  inline, byte-idéntica a como estaba event-domain antes de este fix (confirmado leyendo el
  archivo, solo de lectura, no se tocó). El `FileUsageService` de catalog-domain es una clase
  distinta y no relacionada (reporta usos de un file ID para el endpoint interno de auditoría de
  archivos, LAYER-03) — no es un precedente a espejar aquí. Por lo tanto la extracción de este
  repo queda **standalone**, sin coordinación cross-repo (no se creó ningún path-repository ni se
  tocó `ci4-api-core`, por instrucción explícita). **Pendiente en catalog-domain**: aplicar la
  misma extracción allá (`PublicCollectionItemController::resolveMediaFields()` →
  `App\Services\Catalog\CollectionItemMediaResolutionService` o nombre equivalente, mismo patrón
  de `FileUsageService`) — queda fuera del alcance de este repo, no se tocó
  `teatromuseo-catalog-domain`.
- **Tests nuevos**: `tests/Unit/Services/Events/EventMediaResolutionServiceTest.php` (4 casos,
  puro con `HubClient` mockeado — sin ir a caso de sin-IDs, cover+gallery con metadata mixta
  (variants como array/null/JSON-string), metadata ausente, e IDs de galería inválidos/CSV con
  ruido). `tests/Feature/Controllers/Events/PublicEventControllerTest.php` no cambió — sus fixtures
  no usan `cover_file_id`/`gallery_file_ids`, así que ya ejercitaban el camino sin llamar al Hub y
  siguen pasando sin cambios.
- **Verificado**: `composer phpstan` ✅ (nivel 8, sin errores), `composer cs-check` ✅, 243 tests /
  602 assertions / 1 skip ✅ (+4 tests / +12 assertions sobre el baseline de 239/590 — exactamente
  los 4 tests nuevos del servicio), 17 tests de arquitectura ✅, `swagger-validate` ✅ (sin diff —
  el contrato público no cambió, mismo shape de respuesta), `i18n-check`/`docs-i18n-check` ✅.

### LAYER-03 + LAYER-04 + verificación LAYER-01/LAYER-06 — Fase 4, coherencia de capas (2026-08-06)

- **LAYER-03 — deduplicado el bloqueo de inventario de `BookingService`.** `beforeStore()`
  (decremento al crear un hold) y `beforeUpdate()` (incremento al cancelar/expirar) tenían el
  mismo bloque de `$db->table(...)->set('available_spots', ...)->update()` sobre `ticket_types`
  y `occurrences` duplicado byte-por-byte dentro de la misma clase. Extraído a un único método
  privado `BookingService::adjustInventory()`, que delega el UPDATE atómico a un método nuevo de
  modelo compartido — `TicketTypeModel`/`OccurrenceModel::adjustAvailableSpots()`, vía trait
  `App\Traits\Models\HasAvailableSpots` — que encapsula el `WHERE available_spots >= N` que evita
  oversell en decrementos (sin guardia en incrementos, que siempre deben liberar el cupo). El
  builder crudo queda encapsulado en el Model, no en el Service. También `FileUsageService.php:41`
  (acceso directo a `BaseConnection::table('events')` desde el Service) migrado a un método nuevo
  `EventModel::findReferencingHubFile()`; `FileUsageService` ahora depende de `EventModel` en vez
  de `BaseConnection` (wiring actualizado en `EventsDomainServices::fileUsageService()`).
  `ServiceModelDependencyConventionsTest::ALLOWED` amplíado con `FileUsageService.php` (excepción
  justificada y documentada, no un nuevo defecto). **Nota de alcance:** cms-domain y
  catalog-domain tienen el mismo patrón de `FileUsageService` con `BaseConnection` cruda — no se
  tocaron por estar fuera de este repo; quedan como candidatos si se decide unificar el patrón
  cross-repo más adelante.
- **LAYER-04 — `ControllerModelDependencyConventionsTest` ya existía** en el working tree (archivo
  sin trackear de una pasada previa sin commitear), con baseline vacío. Verificado que corre y
  pasa sin violaciones reales — no fue necesario portar nada nuevo ni agregar whitelist.
- **LAYER-01 y LAYER-06 — verificación de checkboxes desactualizados, no trabajo de esta pasada.**
  LAYER-06 (retiro de `start_time`/`end_time`/`venue`/`capacity`/`available_spots` de
  `EventModel` y `EventResponseDTO`) confirmado **completo** contra el código ya presente
  (sin commitear) en el working tree. LAYER-01 confirmado **parcial**: 2 de sus 4 sub-problemas
  ya resueltos (mutación de `$_GET` movida a `EventService::indexPublicCartelera()`; `$dto` sin
  usar en `show()` corregido), pero **2 siguen abiertos** — `PublicEventController` sigue
  llamando `Services::hubClient()` directamente y su `resolveMediaFields()` sigue siendo
  byte-idéntica a la de `PublicCollectionItemController` en catalog-domain. TASKS.md corregido
  para reflejar el estado real en vez de marcarlo completo.
- **Verificado:** `composer cs-check` ✅, `composer phpstan` ✅ (nivel 8, sin errores), 238 tests /
  589 assertions ✅ (incluye la suite completa de `BookingServiceIntegrationTest` — ciclo de hold,
  sold-out, ocurrencia programada — sin cambios de comportamiento), 16 tests de arquitectura ✅,
  `i18n-check`/`docs-i18n-check` ✅. `swagger-validate` sigue fallando por el mismo motivo ya
  documentado en la entrada CORE-01+CORE-03 (diff de `public/swagger.json` pendiente de commit
  del trabajo en curso de LAYER-06, no causado por LAYER-03/04 — confirmado: el diff no cambió de
  tamaño antes/después de estos cambios).

### CORE-02 — Filtros a las bases del paquete v1.3.0 (2026-08-06, segunda pasada)

- **SEC-02 finalmente unificado.** `PermissionFilter` extiende `AbstractPermissionFilter`
  (`ci4-api-core` v1.3.0). Esta app ya tenía el bypass de superadmin desde antes; ahora vive como
  `superAdminBypassCode()` en el paquete, y **cms y catalog lo adoptaron también** — las tres
  apps se comportan igual por primera vez. Como `app/Language/{es,en}/Auth.php` no define
  `authRequired`/`insufficientPermissions` (solo `rateLimitExceeded`), se sobrescribieron
  `unauthenticatedMessage()`/`forbiddenMessage()` para seguir leyendo `Api.authRequired`/
  `Api.insufficientPermissions`. Nuevo test `tests/Unit/Filters/PermissionFilterTest.php`
  (6 casos — esta app no tenía ninguno antes, pese a ser la única con el bypass).
- `HubSignatureFilter` y `WebAppKeyRequiredFilter` ahora extienden
  `AbstractHubSignatureFilter`/`AbstractWebAppKeyRequiredFilter`. Nuevo test
  `WebAppKeyRequiredFilterTest.php` (4 casos, no existía antes).
- **`app/Traits/Controllers/HasCrudActions.php` resultó ser código muerto, no boilerplate en
  uso.** Byte-idéntico en api/cms/catalog/event, pero ningún controlador real lo consumía. Se
  eliminó en vez de migrarse.
- **Sigue sin reconciliar:** el `onlyEntities()` de `AuditLogModel` (catalog y event lo tienen,
  api y cms no) y el drift de esquema en `jobs`/`request_logs`/`audit_logs`/`idempotency_keys` —
  sin base compartida en el paquete para esto, `core:install` no ayuda porque las 4 apps ya
  tienen sus propias migraciones con ese nombre de clase.

**Verificación:** 237 tests / 588 assertions ✅, PHPStan sin errores, CS limpio.

### CORE-01 + CORE-03 — Localización y Config\Api al paquete (2026-08-06)

- **CORE-01:** eliminado el fork local de localización (`Libraries/Localization/*` y
  `Traits/Services/Has*`) — **1.131 líneas menos**. Ahora se consume el runtime de
  `ci4-api-core` v1.2.0, que se construyó tomando **esta** implementación como referencia: las dos
  divergencias funcionales que tenía con catalog (el respaldo de slug y el `$data['id']` en
  `beforeUpdate()`) están resueltas aguas arriba. `TranslationFieldCatalog` quedó absorbido por
  `Config\Localization`. `EventTranslationModel` y `EventPublicSlugModel` extienden ahora las bases
  del paquete.
- **CORE-03:** `app/Config/Api.php` pasa de 148 líneas copiadas a extender la base del paquete.
- **Variable de entorno:** `EVENT_LEGACY_FALLBACK_LOCALE` → `LOCALIZATION_LEGACY_FALLBACK_LOCALE`
  (compartida por todos los dominios). Actualizado `.env.example`; el valor por defecto no cambia.
- **Test actualizado:** `LocalizedTranslationStoreTest` construía el store con la firma antigua
  `(model, ?IncomingRequest)`; ahora usa `(model, RequestLocaleResolver, Config\Localization)`.
- **Test de arquitectura robustecido:** `AuditableModelConventionsTest` ahora recorre la cadena de
  herencia en vez de comparar el texto de `extends`.

**Verificación:** 227 tests / 572 assertions ✅, PHPStan sin errores, CS limpio.
⚠️ `composer quality` sigue fallando en `swagger-validate` mientras `public/swagger.json` esté
regenerado pero sin commitear — es el estado del trabajo en curso sobre `LAYER-06`, no un defecto.

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
