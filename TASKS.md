# TASKS — teatromuseo-event-domain

> Trabajo abierto de este repositorio. Programa cross-repo:
> [`../TASKS.md`](../TASKS.md). Cierres históricos:
> [`TASKS_ARCHIVE.md`](TASKS_ARCHIVE.md).

## ✅ Completadas

- [x] **PERF-03 — Retirar `EventService::indexPublicCartelera()` y la ruta
  `GET public/events`** — cerrada 2026-08-13, ejecutando §2.5/§2.F de
  [`../docs/audits/2026-08-12-auditoria-parte2-rendimiento-listados-publicos.md`](../docs/audits/2026-08-12-auditoria-parte2-rendimiento-listados-publicos.md).
  Verificado (agente de investigación dedicado, no solo grep superficial):
  `PublicEventController::index()` era el único caller de
  `indexPublicCartelera()` (paginación completa en PHP + `usort()`) y además
  hacía N+1 de llamadas HTTP al Hub por evento (`foreach` +
  `resolveMediaFields()`); cero consumidores en teatromuseo-web/bff/admin/totem
  (`teatromuseo-web` migró a `public-read/{lang}/events`). Se borró el
  método del controlador, `indexPublicCartelera()` y `firstOccurrenceStart()`
  de `EventService`, la declaración en `EventServiceInterface`, la ruta y su
  bloque OpenAPI, 3 tests completos + 1 test de compatibilidad legacy en
  `PublicReadQueryBudgetTest` + edición parcial de 1 test más (se conservó el
  check de `show()` 404). `show()` y `types()` quedaron intactos (no eran el
  patrón N+1 auditado, aunque `show()` también carece de consumidores
  confirmados — decisión explícita de no expandir el alcance más allá de lo
  auditado). Verificado: 270/270 tests, PHPStan 0 errores, CS-Fixer limpio,
  swagger.json regenerado.

- [x] **PERF-02 — Índice compuesto en events + cerrar punto ciego de test
  EXPLAIN** — cerrada 2026-08-13. Migración
  `idx_events_public_listing (status, deleted_at, event_type)`. Medido: para
  el sort "agenda" (default), MySQL prefiere driving desde
  `occurrence_projection` + `PRIMARY` eq_ref en `events` (plan ya óptimo, sin
  full scan); el índice nuevo sí es seleccionado (`type=ref`, `Using index`)
  para un filtro directo de `events` sin ese join — el patrón que el índice
  existe para cubrir. Test extendido para verificar el plan de `events`
  (antes solo se verificaba `occurrence_projection`/`occurrences`) en vez de
  asumir una key específica sin medir. Verificado: 274/274 tests, PHPStan 0
  errores, CS-Fixer limpio. Evidencia:
  [`../docs/audits/2026-08-12-auditoria-parte2-rendimiento-listados-publicos.md`](../docs/audits/2026-08-12-auditoria-parte2-rendimiento-listados-publicos.md).

- [x] **ADM-DASH-02 — Read model acotado para el dashboard Admin** — cerrada
  2026-08-11. Endpoint autenticado y permission-aware con conteos y actividad
  reciente bounded para eventos, programación, recintos, funciones y ticketing;
  contrato, OpenAPI y prueba de integración de columnas reales verificados.

- [x] **QA-01 — Contract tests y OpenAPI** — cerrada 2026-08-10. Contrato
  PublicRead Events, OpenAPI, auth, envelope, fallback, regresión CRUD y
  estados verificados. Evidencia en [`../docs/audits/2026-08-10-qa-01-contractos-openapi.md`](../docs/audits/2026-08-10-qa-01-contractos-openapi.md).
- [x] **QA-02 — EXPLAIN, índices y budgets SQL** — cerrada 2026-08-10. Listing
  medido con 1.000 eventos/160 occurrences, máximo 6 queries/500 ms SQL y
  `idx_occurrences_public_read` para `EXISTS`/`MIN`/`MAX`; sin N+1. Evidencia
  en [`../docs/audits/2026-08-10-qa-02-explain-indexes.md`](../docs/audits/2026-08-10-qa-02-explain-indexes.md).
- [x] **QA-03 — Carga fría/caliente/degradada y single-flight** — cerrada
  2026-08-10 como tarea raíz cross-repo; evidencia en
  [`../docs/audits/2026-08-10-qa-03-cache-concurrency.md`](../docs/audits/2026-08-10-qa-03-cache-concurrency.md).
- [x] **QA-04 — Paridad y shadow comparison** — cerrada 2026-08-10 como tarea
  raíz cross-repo; evidencia en
  [`../docs/audits/2026-08-10-qa-04-paridad-shadow.md`](../docs/audits/2026-08-10-qa-04-paridad-shadow.md).

## 🔴 En progreso

- [ ] **REL-01** — Activación controlada; pendiente de ventana de cutover y
  baseline/shadow del runtime anterior.

## 🟡 Próximo

### Plan vigente — PublicRead/PageDelivery/Snapshots (2026-08-09)

`PUB-00`, `PUB-01/02`, `EVT-01..03`, `SHARED-01` y `CACHE-03` están cerradas
y archivadas. Este repo participa ahora en:


Estas casillas reflejan la tarea raíz; no duplicar la implementación ni medir
dos veces el mismo contrato desde el tracker local.

### Saneamiento arquitectónico heredado (prioridad 2)

- [ ] **CORE-06** — Unificar permisos solo con ventana de mantenimiento y
  migración explícita de `permissions`/`role_permissions` en el Hub.
- [ ] **MIG-01** — Consolidar la cadena de backfill de slugs de tipos de evento
  en una migración idempotente.
- [ ] **MIG-02** — Añadir claves foráneas a traducciones/slugs y decidir una
  convención única para los estados de dominio.
- [ ] **MIG-03** — Definir y añadir un camino de seed/bootstrap idempotente.
- [ ] **HYG-01** — Purgar/rotar `writable/debugbar` y `writable/logs` sin tocar
  datos de producción.
- [ ] **DEAD-02** — Retirar directorios vacíos y corregir el `strict_types` de
  la ruta generada, validando después el gate de estilo.

### Dependencias y conflictos

- `QA-02` quedó cerrada; cualquier cambio posterior de índices o claves exige
  repetir sus regresiones antes de QA-03/QA-04.
- `MIG-01/02/03` puede afectar slugs, contratos o fixtures usados por `QA-01`;
  no ejecutarlos en paralelo con la verificación pública.
- `CORE-06` queda fuera del cutover y requiere la misma migración coordinada
  del Hub que CMS y Catalog.

## 🏗️ Contratos de arquitectura

- Lecturas públicas separadas del CRUD administrativo, con fieldsets y envelopes
  versionados.
- Autenticación delegada al Hub; no decodificar JWT localmente.
- Permisos con `.` y no con `:`.
- Medios resueltos en batch; no hacer llamadas individuales al Hub desde los
  controladores públicos.
