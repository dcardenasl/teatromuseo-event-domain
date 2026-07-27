# Modelo de programación de eventos

Este dominio posee la programación operativa de Teatro Museo. No posee las
páginas editoriales, el contenido del catálogo museográfico ni los usuarios.

## Estructura del aggregate

`Event` es la raíz del aggregate de programación. Identifica la actividad
programada: función, festival, curso, taller u otra actividad pública.

`Occurrence` es una sesión concreta de un evento. Las fechas, horarios,
espacio, cupos, disponibilidad y estado operativo pertenecen a las
ocurrencias. Esto permite que una obra, festival, curso o taller tenga muchas
sesiones sin duplicar el evento principal.

`Venue` es un espacio operativo reutilizable. Un venue puede ser utilizado por
muchas ocurrencias y puede retirarse sin eliminar ocurrencias históricas.

`TicketType`, `Booking` y `Ticket` forman la capa opcional de control de acceso.
Los tipos de entrada terminarán perteneciendo a una ocurrencia porque la
disponibilidad corresponde a una sesión concreta, no al evento general.

`EventReference` almacena vínculos hacia registros de otros sistemas, como un
item de Catalog, una página de CMS o un registro legacy. Estas referencias usan
`source_system`, `source_type` y `source_id`; nunca crean foreign keys entre
bases de datos de distintos dominios.

## Transición desde el scaffold inicial

La primera migración de `events` contiene actualmente `start_time`,
`end_time`, `venue`, `capacity` y `available_spots`. Esos campos representan
datos de una ocurrencia. La transición será:

1. Crear `venues` y `occurrences`.
2. Convertir cada agenda existente del evento en una ocurrencia.
3. Mover la pertenencia de los tipos de entrada desde `event_id` hacia
   `occurrence_id`.
4. Mantener compatibilidad solo durante la migración.
5. Eliminar o deprecar las columnas de agenda de `events` antes de importar el
   legacy.

## Código generado y código manual

El scaffolder CRUD es la fuente de verdad para los recursos repetitivos, como
venues, ocurrencias y referencias externas. Genera DTOs, servicios,
controladores, migraciones, rutas, permisos, documentación OpenAPI y tests.

El aggregate de eventos y las operaciones de ticketing requieren extensiones
manuales para operaciones anidadas e invariantes: orden temporal, conflictos
de espacios, capacidad, disponibilidad, reservas idempotentes y transiciones
seguras de estado.

## Reglas entre dominios y contenido

- Las descripciones y etiquetas siguen la estrategia de traducciones del
  proyecto.
- Los registros de Catalog y CMS se vinculan mediante referencias externas,
  nunca mediante foreign keys entre bases de datos.
- Los endpoints públicos exponen solo eventos y ocurrencias publicados.
- Bookings, tickets, auditoría y campos administrativos permanecen privados.
