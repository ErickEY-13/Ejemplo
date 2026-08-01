# Módulo frontend: Registro de Personas

> Responsable: **Desarrollador 1**

Se carga de forma perezosa desde `app.routes.ts` bajo la ruta `/personas`.
Todo el código de personas vive aquí dentro; no importa nada de
`features/vehicles` ni al revés.

## Estructura

```
persons/
├── models/person.model.ts     # Espejo tipado de PersonResource (backend)
├── pages/
│   ├── person-list/           # Listado con filtros, orden y paginación
│   └── person-form/           # Alta y edición (misma pantalla)
├── services/person.service.ts # Única puerta hacia /api/persons
└── persons.routes.ts          # Rutas del módulo
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

## Campo `site` (sede)

El formulario incluye "Sede" (oficina o sector de la municipalidad al que
pertenece la persona), con sugerencias tomadas de `metadata.sites`. Lo
consume el módulo `features/assignments` (a través de su propio backend,
`/api/assignments/people`) para mostrar de qué sede es el responsable
asignado a un vehículo — este módulo no importa nada de `features/vehicles`
ni de `features/assignments`, ni falta que le haga.
