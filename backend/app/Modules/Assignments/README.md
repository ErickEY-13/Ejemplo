# Módulo: Asignación de Vehículos

> Puente entre **Vehículos** y **Personas**.

Persons y Vehicles no se importan entre sí a propósito (así los dos módulos
evolucionan por separado). Cuando dos módulos necesitan relacionarse, esa
relación vive en un tercer módulo neutral como este: `Assignments` es el
único que conoce clases de ambos (`Vehicle` y `Person`).

Un vehículo tiene como máximo **una asignación activa** (responsable
actual). Reasignar reemplaza la fila anterior; no se guarda historial de
asignaciones pasadas.

## Estructura

```
Assignments/
├── Database/Migrations/   # vehicle_assignments (vehicle_id único, person_id, assigned_at, notes)
├── Http/
│   ├── Controllers/       # Solo traduce HTTP <-> servicio
│   ├── Requests/          # Validación (AssignVehicleRequest)
│   └── Resources/         # Contrato JSON de salida
├── Models/                # VehicleAssignment
├── Providers/             # AssignmentsServiceProvider (autodescubierto)
├── Routes/api.php         # Se publica bajo /api/assignments
└── Services/              # AssignmentService (lógica de negocio)
```

## Endpoints

| Método       | Ruta                        | Descripción                                    |
| ------------ | --------------------------- | ----------------------------------------------- |
| `GET`        | `/api/assignments/people`   | Búsqueda liviana de personas activas (selector) |
| `GET`        | `/api/assignments/{vehicle}`| Asignación actual del vehículo, o `data: null`  |
| `PUT\|PATCH` | `/api/assignments/{vehicle}`| Asignar o reasignar (`person_id`, `notes?`)     |
| `DELETE`     | `/api/assignments/{vehicle}`| Quitar la asignación                            |

`{vehicle}` es el id del vehículo (no de la asignación): cada vehículo
expone su asignación bajo su propio id, ya que solo puede tener una activa.

Si la persona asignada se elimina más adelante (borrado lógico), la
asignación sigue mostrando su nombre (la relación usa `withTrashed`), con
`person.deleted_at` distinto de `null` para que el frontend pueda marcarlo.

## Comandos habituales

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan test --filter=Assignment
```
