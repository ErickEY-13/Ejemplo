# Módulo: Asignación de Vehículos

> Puente entre **Vehículos** y **Personas**.

Persons y Vehicles no se importan entre sí a propósito (así los dos módulos
evolucionan por separado). Cuando dos módulos necesitan relacionarse, esa
relación vive en un tercer módulo neutral como este: `Assignments` es el
único que conoce clases de ambos (`Vehicle` y `Person`).

Cada fila de `vehicle_assignments` es un registro **histórico e inmutable**
de vehículo + sede + responsable (opcional). `ended_at = null` significa que
sigue vigente. Trasladar un vehículo de sede o reasignarlo no modifica la
fila activa: la cierra (`ended_at = now()`) y crea una nueva. Así queda
trazabilidad completa de dónde estuvo cada vehículo y quién lo tuvo a cargo.

## Estructura

```
Assignments/
├── Database/Migrations/   # sites, vehicle_assignments (con site_id, ended_at, person_id opcional)
├── Http/
│   ├── Controllers/       # Solo traduce HTTP <-> servicio
│   ├── Requests/          # Validación (AssignVehicleRequest)
│   └── Resources/         # Contrato JSON de salida
├── Models/                # VehicleAssignment, Site
├── Providers/             # AssignmentsServiceProvider (autodescubierto)
├── Routes/api.php         # Se publica bajo /api/assignments
└── Services/              # AssignmentService (lógica de negocio)
```

## Endpoints

| Método       | Ruta                          | Descripción                                          |
| ------------ | ------------------------------ | ----------------------------------------------------- |
| `GET`        | `/api/assignments?site_id=`   | Asignación actual de todos los vehículos, filtrable   |
| `GET`        | `/api/assignments/sites`      | Catálogo de sedes                                     |
| `GET`        | `/api/assignments/people`     | Búsqueda liviana de personas activas (selector)       |
| `GET`        | `/api/assignments/{vehicle}`  | Asignación actual del vehículo, o `data: null`        |
| `GET`        | `/api/assignments/{vehicle}/history` | Historial completo del vehículo                |
| `PUT\|PATCH` | `/api/assignments/{vehicle}`  | Trasladar/(re)asignar (`site_id`, `person_id?`, `notes?`) — cierra la anterior y crea una nueva |
| `DELETE`     | `/api/assignments/{vehicle}`  | Cerrar la asignación activa (no borra el historial)   |

`{vehicle}` es el id del vehículo (no de la asignación): cada vehículo
expone su asignación vigente bajo su propio id.

Si la persona asignada se elimina más adelante (borrado lógico), la
asignación sigue mostrando su nombre (la relación usa `withTrashed`), con
`person.deleted_at` distinto de `null` para que el frontend pueda marcarlo.

## Comandos habituales

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan test --filter=Assignment
```
