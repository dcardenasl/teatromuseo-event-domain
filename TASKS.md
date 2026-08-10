# TASKS — teatromuseo-event-domain

> Trabajo abierto de este repositorio. Programa cross-repo:
> [`../TASKS.md`](../TASKS.md). Cierres históricos:
> [`TASKS_ARCHIVE.md`](TASKS_ARCHIVE.md).

## ✅ Completadas

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
