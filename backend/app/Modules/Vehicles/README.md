# Módulo: Registro de Vehículos

> Responsable: **Desarrollador 2**

Todo el código de vehículos vive dentro de esta carpeta. Ningún otro módulo
debería importar clases de aquí, ni al revés: así los dos módulos evolucionan
por separado y los merges no chocan.

## Estructura

```
Vehicles/
├── Database/
│   ├── Factories/      # VehicleFactory (datos de prueba)
│   ├── Migrations/     # Se cargan solas (loadMigrationsFrom)
│   └── Seeders/        # VehiclesSeeder, lo llama DatabaseSeeder
├── Enums/              # VehicleType, FuelType
├── Http/
│   ├── Controllers/    # Solo traduce HTTP <-> servicio
│   ├── Requests/       # Validación
│   └── Resources/      # Contrato JSON de salida
├── Models/             # Vehicle (tabla `vehicles`)
├── Providers/          # VehiclesServiceProvider (autodescubierto)
├── Routes/api.php      # Se publica bajo /api/vehicles
└── Services/           # VehicleService (lógica de negocio)
```

## Endpoints

| Método       | Ruta                           | Descripción                          |
| ------------ | ------------------------------ | ------------------------------------ |
| `GET`        | `/api/vehicles`                | Listado paginado con filtros         |
| `GET`        | `/api/vehicles/metadata`       | Catálogos para los formularios       |
| `POST`       | `/api/vehicles`                | Crear                                |
| `GET`        | `/api/vehicles/{id}`           | Detalle                              |
| `PUT\|PATCH` | `/api/vehicles/{id}`           | Actualizar                           |
| `DELETE`     | `/api/vehicles/{id}`           | Eliminar (borrado lógico)            |
| `POST`       | `/api/vehicles/{id}/restore`   | Restaurar un registro eliminado      |
| `POST`       | `/api/vehicles/{id}/photo`     | Subir o reemplazar la foto           |
| `DELETE`     | `/api/vehicles/{id}/photo`     | Quitar la foto                       |

### Filtros de `GET /api/vehicles`

| Parámetro      | Tipo    | Notas                                                              |
| -------------- | ------- | ------------------------------------------------------------------ |
| `search`       | string  | Placa, marca, modelo o VIN                                         |
| `type`         | enum    | `car`, `motorcycle`, `truck`, `van`, `bus`, `other`                |
| `fuel_type`    | enum    | `gasoline`, `diesel`, `gas`, `hybrid`, `electric`                  |
| `brand`        | string  | Coincidencia exacta                                                |
| `year_from`    | int     | —                                                                  |
| `year_to`      | int     | Debe ser `>= year_from`                                            |
| `is_active`    | boolean | —                                                                  |
| `with_trashed` | boolean | Incluye los eliminados                                             |
| `sort`         | string  | `id`, `plate`, `brand`, `model`, `year`, `mileage`, `created_at`   |
| `direction`    | string  | `asc` \| `desc`                                                    |
| `per_page`     | int     | 1–100 (por defecto 15)                                             |

## Foto del vehículo

Una sola foto por vehículo, guardada en el disco `public` (ya expuesto vía
el symlink `public/storage`, sin configuración adicional).

- `POST /api/vehicles/{id}/photo`: campo `photo` (multipart), validado como
  `image|mimes:jpeg,jpg,png,webp|max:4096` (4 MB). Si ya había una foto,
  se borra del disco antes de guardar la nueva.
- `DELETE /api/vehicles/{id}/photo`: borra la foto actual, si existe.
- Ambos devuelven el vehículo actualizado; `photo_url` es `null` cuando no
  tiene foto.

## Responsable asignado

Un vehículo puede tener un responsable (persona) asignado. Esa relación no
vive aquí: la gestiona el módulo `Assignments` (`/api/assignments/{id}`),
que es el único que conoce tanto a `Vehicle` como a `Person`. Este módulo no
importa nada de `Assignments` ni de `Persons` — ver
[`backend/app/Modules/Assignments/README.md`](../Assignments/README.md).

## Comandos habituales

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
docker compose exec app php artisan test --filter=Vehicle
```
