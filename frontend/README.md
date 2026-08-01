# Frontend (Angular 22)

SPA que consume la API REST de Laravel. **No se ejecuta directamente**: vive
dentro del contenedor `frontend`. Consulta el [README principal](../README.md)
para levantar el entorno.

```bash
docker compose up -d
# -> http://localhost:4200
```

## Organización

```
src/
├── environments/       # apiUrl y flags por entorno
├── styles.scss         # Tokens de diseño y primitivas compartidas
└── app/
    ├── core/           # Cliente HTTP, interceptor de errores, avisos, menú
    ├── shared/         # Componentes y utilidades reutilizables
    └── features/       # ◄── Un módulo por dominio de negocio
        ├── home/       #     Menú principal
        ├── persons/    #     Desarrollador 1
        └── vehicles/   #     Desarrollador 2
```

Reglas de la casa:

- Un módulo **nunca** importa de otro módulo. Lo común va en `core/` o `shared/`.
- Los módulos se cargan de forma perezosa desde `app.routes.ts`.
- Los componentes son *standalone*, con señales y `ChangeDetectionStrategy.OnPush`.
- La aplicación es *zoneless*: el estado se modela con señales, no con mutaciones
  que dependan de Zone.js para refrescar la vista.

## Convenciones

| Elemento              | Convención                                             |
| --------------------- | ------------------------------------------------------ |
| Página (ruta)         | `pages/<nombre>/<nombre>.page.ts`                      |
| Servicio de módulo    | `services/<entidad>.service.ts` — única puerta a la API |
| Modelo                | `models/<entidad>.model.ts` — espejo del Resource de Laravel |
| Rutas del módulo      | `<modulo>.routes.ts`, exporta `<MODULO>_ROUTES`        |

## Comandos

Todos se ejecutan dentro del contenedor:

```bash
docker compose exec frontend npm test
```

```bash
docker compose exec frontend npm run build
```

```bash
docker compose exec frontend npx ng generate component features/persons/pages/algo
```

## Conexión con la API

En desarrollo, el dev-server hace de proxy de `/api` hacia el contenedor
`nginx` (ver `proxy.conf.js`), y en producción es Nginx quien sirve el SPA y la
API desde el mismo origen. Por eso `environment.apiUrl` es `/api` en ambos
casos y no hace falta gestionar CORS.
