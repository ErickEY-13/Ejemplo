# Módulo frontend: Registro de Vehículos

> Responsable: **Desarrollador 2**

Se carga de forma perezosa desde `app.routes.ts` bajo la ruta `/vehiculos`.
Todo el código de vehículos vive aquí dentro; no importa nada de
`features/persons` ni al revés.

## Estructura

```
vehicles/
├── models/vehicle.model.ts      # Espejo tipado de VehicleResource (backend)
├── pages/
│   ├── vehicle-list/            # Listado con filtros, orden y paginación
│   └── vehicle-form/            # Alta y edición (misma pantalla)
├── services/vehicle.service.ts  # Única puerta hacia /api/vehicles
└── vehicles.routes.ts           # Rutas del módulo
```

## Qué se puede tocar sin coordinarse

- Todo lo que hay bajo esta carpeta.
- El archivo `app.routes.ts` solo se toca al crear un módulo nuevo.

## Lo compartido (avisar antes de cambiarlo)

- `core/` — cliente HTTP, interceptor de errores, avisos.
- `shared/` — paginador, spinner, estado vacío, diálogo de confirmación.
- `styles.scss` — tokens de diseño y primitivas.

Si necesitas un componente reutilizable nuevo, créalo en `shared/` y avísale
al otro desarrollador; si solo lo usa este módulo, déjalo aquí dentro.
