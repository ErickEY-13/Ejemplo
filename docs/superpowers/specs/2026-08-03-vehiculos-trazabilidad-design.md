# Trazabilidad de Vehículos (sede, traslados y responsable histórico)

## Contexto y problema

Hoy el módulo de asignaciones (`Modules/Assignments`) modela la relación vehículo-responsable como un **estado único que se sobrescribe**:

- `vehicle_assignments.vehicle_id` es `UNIQUE`: un vehículo tiene como máximo una asignación activa.
- `AssignmentService::assign()` usa `updateOrCreate`, reemplazando la fila anterior sin dejar rastro.
- `AssignmentService::unassign()` **borra físicamente** la fila.
- `Vehicle` no tiene ningún concepto de sede/ubicación.
- El sistema genérico de auditoría (`Audit`) solo está conectado a `Person`, no a `Vehicle` ni `VehicleAssignment`.

Resultado: es imposible responder "¿en qué sede está este vehículo?", "¿de qué sede a qué sede se movió?" o "¿quién lo tuvo asignado antes?".

## Objetivo

Registrar de forma histórica e inmutable, para cada vehículo: en qué sede estuvo, quién fue su responsable (si lo hubo) y durante qué período, de modo que se pueda reconstruir la trazabilidad completa de movimientos y responsables.

## Decisiones de diseño (confirmadas con el usuario)

1. **Sede y responsable van juntos en un mismo registro de asignación** (no dos historiales separados). Un traslado de sede se modela como una nueva asignación aunque el responsable no cambie.
2. **Se crea una entidad `Site` (sede) propia**, con tabla en base de datos — no se reutiliza el enum `Site` de `Person` como código compartido, para no ampliar el alcance tocando el módulo `Persons`. Las sedes iniciales de la nueva tabla **coinciden en valores** con las del enum de `Person` (main, north, south, east, west, annex) para mantener consistencia conceptual.
3. **El responsable (`person_id`) es opcional**: puede existir un registro "vehículo en sede X, sin responsable" (ej. vehículo disponible en cochera).
4. **Al crear una nueva asignación, la anterior se cierra automáticamente** (se le asigna `ended_at`), en un único paso — no hay un botón separado de "finalizar" antes de reasignar.
5. **Sin restricción de rol/permiso adicional**: cualquier usuario con acceso al módulo puede registrar traslados/reasignaciones, igual que hoy.
6. **Frontend con dos vistas**: (a) listado general de vehículos con sede y responsable actuales + filtro por sede, y (b) vista de historial/timeline por vehículo.
7. **No se integra con el sistema genérico `Audit`**: la tabla de historial de asignaciones ya cumple ese rol de forma más rica (sede, responsable, fechas); conectarlo a `Audit` sería redundante.

## Modelo de datos

### Tabla nueva: `sites`

| columna | tipo | notas |
|---|---|---|
| id | bigint PK | |
| code | string, unique | ej. `main`, `north`... |
| name | string | nombre visible, ej. "Sede Principal" |
| timestamps | | |

Seed inicial con las 6 sedes que hoy usa el enum `Site` de `Person`.

### Tabla `vehicle_assignments` (modificada)

Cambios sobre la tabla actual:

- **Quitar** el índice `UNIQUE` en `vehicle_id`.
- **Agregar** `site_id` (FK a `sites`, `nullable`). Nullable porque las filas migradas desde el estado actual no tienen sede conocida (nunca se registró); las filas nuevas la exigen a nivel de validación de formulario, no de constraint de BD.
- **Agregar** `ended_at` (timestamp, nullable). `ended_at IS NULL` ⇒ asignación activa/actual.
- Mantener `person_id` (ya nullable), `assigned_at` (representa `started_at`), `expected_return_at`, `notes`.
- **Agregar índice** compuesto `(vehicle_id, ended_at)` para resolver eficientemente "asignación actual de un vehículo".

### Migración de datos existentes

Las filas actuales de `vehicle_assignments` (a lo sumo una por vehículo, ya que hoy es 1:1) se conservan: quedan con `ended_at = NULL` (siguen activas) y `site_id = NULL` (sede desconocida, se muestra como "sede no registrada" en el frontend hasta el próximo traslado).

## Backend (Laravel)

### `Vehicle`
- Nueva relación `currentAssignment()`: `HasOne` sobre `VehicleAssignment` filtrando `ended_at IS NULL` (usando `ofMany` para tomar la más reciente si hubiera inconsistencias).

### `VehicleAssignment`
- Relaciones: `belongsTo(Vehicle::class)`, `belongsTo(Person::class)` (nullable), `belongsTo(Site::class)` (nullable).
- Scope `active()`: `whereNull('ended_at')`.

### `AssignmentService`
- `assign(Vehicle $vehicle, ?Person $person, Site $site, array $data = [])`: dentro de una transacción, cierra la asignación activa existente (`ended_at = now()`) si la hay, y crea una nueva fila (`started_at = now()`, `ended_at = null`, `site_id`, `person_id`, `notes`). Reemplaza el `updateOrCreate` actual.
- `unassign(Vehicle $vehicle)`: cierra la asignación activa (`ended_at = now()`) en lugar de borrarla.
- `history(Vehicle $vehicle)`: retorna todas las asignaciones del vehículo, ordenadas por `started_at` descendente, con `person` y `site` cargados (eager loading).
- `current(Vehicle $vehicle)`: retorna la asignación activa o `null`.

### `AssignmentController`
- `GET /api/assignments/{vehicle}`: asignación actual (comportamiento equivalente al de hoy).
- `PUT|PATCH /api/assignments/{vehicle}`: crea nueva asignación (cierra la anterior automáticamente). Requiere `site_id`; `person_id` opcional.
- `DELETE /api/assignments/{vehicle}`: cierra la asignación activa (ya no borra la fila).
- **Nuevo** `GET /api/assignments/{vehicle}/history`: historial completo del vehículo.

### Validación
- `site_id` requerido al crear/actualizar una asignación (FormRequest).
- `person_id` opcional, debe existir en `persons` si se envía.

## Frontend (Angular)

### Modelos/servicios
- Nuevo modelo `Site` (`id`, `code`, `name`).
- `VehicleAssignment` actualizado: agrega `site`, `startedAt`, `endedAt`; `person` pasa a ser opcional.
- Servicio de asignaciones: agrega `getHistory(vehicleId)` y `getSites()`.

### Listado de vehículos
- Nueva columna "Sede actual" y "Responsable actual".
- Filtro por sede.

### Vista de historial por vehículo
- Nueva pestaña/sección en el detalle del vehículo: tabla cronológica (más reciente primero) con columnas Sede, Responsable, Desde, Hasta. Al comparar dos filas consecutivas se puede leer el traslado "de sede A a sede B".

### Formulario "Trasladar / Reasignar vehículo"
- Campos: sede (select, obligatorio), responsable (autocomplete de personas, opcional), notas.
- Un único botón de envío; el backend se encarga de cerrar la asignación anterior.

## Testing

- **Backend**: tests de `AssignmentService` y de los endpoints HTTP cubriendo — (1) una reasignación cierra la asignación anterior y crea una nueva; (2) `history()` devuelve las asignaciones en el orden correcto con sede/responsable; (3) `unassign()` cierra sin borrar la fila; (4) se puede asignar una sede sin responsable; (5) rechazo de una asignación sin sede.
- **Frontend**: el proyecto no tiene tests de componentes para páginas/features hoy (solo 2 specs de utilidades puntuales: `api.service.spec.ts` y `server-errors.spec.ts`), así que no se introduce ese patrón para esta feature — se sigue la convención existente. La verificación del formulario de traslado, el filtro por sede y la vista de historial se hace manualmente en el navegador (servidor de desarrollo) antes de dar la tarea por terminada.

## Fuera de alcance

- No se modifica el módulo `Persons` ni su enum `Site`.
- No se conecta este flujo al sistema genérico `Audit`.
- No se agregan restricciones de rol/permiso nuevas.
- No se permite backdatar manualmente la fecha de inicio de una asignación (siempre `now()`); no hay edición de asignaciones históricas.
