# Módulo: Registro de Personas

> Responsable: **Desarrollador 1**

Todo el código de personas vive dentro de esta carpeta. Ningún otro módulo
debería importar clases de aquí, ni al revés: así los dos módulos evolucionan
por separado y los merges no chocan.

## Estructura

```
Persons/
├── Database/
│   ├── Factories/      # PersonFactory (datos de prueba)
│   ├── Migrations/     # Se cargan solas (loadMigrationsFrom)
│   └── Seeders/        # PersonsSeeder, lo llama DatabaseSeeder
├── Enums/              # DocumentType, Gender
├── Http/
│   ├── Controllers/    # Solo traduce HTTP <-> servicio
│   ├── Requests/       # Validación
│   └── Resources/      # Contrato JSON de salida
├── Models/             # Person (tabla `people`)
├── Providers/          # PersonsServiceProvider (autodescubierto)
├── Routes/api.php      # Se publica bajo /api/persons
└── Services/           # PersonService (lógica de negocio)
```

## Endpoints

| Método       | Ruta                          | Descripción                          |
| ------------ | ----------------------------- | ------------------------------------ |
| `GET`        | `/api/persons`                | Listado paginado con filtros         |
| `GET`        | `/api/persons/metadata`       | Catálogos para los formularios       |
| `POST`       | `/api/persons`                | Crear                                |
| `GET`        | `/api/persons/{id}`           | Detalle                              |
| `PUT\|PATCH` | `/api/persons/{id}`           | Actualizar                           |
| `DELETE`     | `/api/persons/{id}`           | Eliminar (borrado lógico)            |
| `POST`       | `/api/persons/{id}/restore`   | Restaurar un registro eliminado      |

### Filtros de `GET /api/persons`

| Parámetro       | Tipo    | Notas                                                             |
| --------------- | ------- | ----------------------------------------------------------------- |
| `search`        | string  | Nombre, apellido, documento o correo                              |
| `document_type` | enum    | `national_id`, `foreigner_id`, `passport`, `driver_license`       |
| `is_active`     | boolean | —                                                                 |
| `with_trashed`  | boolean | Incluye los eliminados                                            |
| `sort`          | string  | `id`, `first_name`, `last_name`, `document_number`, `birth_date`, `created_at` |
| `direction`     | string  | `asc` \| `desc`                                                   |
| `per_page`      | int     | 1–100 (por defecto 15)                                            |

## Campo `site` (sede)

Sede u oficina a la que pertenece la persona (texto libre, pensado para una
municipalidad con varios sectores). `GET /api/persons/metadata` expone
`sites`: las sedes ya usadas, para sugerirlas en el formulario.

Lo usa el módulo `Assignments` para mostrar de qué sede es el responsable
asignado a un vehículo — Personas no sabe nada de esa relación, solo expone
el dato.

## Comandos habituales

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app php artisan test --filter=Person
```
