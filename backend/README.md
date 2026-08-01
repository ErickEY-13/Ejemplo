# Backend (Laravel 13)

API REST que consume el SPA de Angular. **No se ejecuta directamente**: vive
dentro del contenedor `app`. Consulta el [README principal](../README.md) para
levantar el entorno.

```bash
docker compose up -d
# -> http://localhost:8000/api/health
```

## Organización

```
app/
├── Http/Controllers/Controller.php   # Controlador base
├── Models/                           # Modelos transversales (User)
├── Modules/                          # ◄── Un módulo por dominio de negocio
│   ├── Persons/                      #     Desarrollador 1
│   └── Vehicles/                     #     Desarrollador 2
├── Providers/
│   ├── AppServiceProvider.php
│   └── ModuleRegistrationServiceProvider.php   # Autodescubre los módulos
└── Support/Module/
    └── ModuleServiceProvider.php     # Clase base de la que hereda cada módulo
```

Cada módulo es autocontenido:

```
app/Modules/Persons/
├── Database/{Factories,Migrations,Seeders}/
├── Enums/
├── Http/{Controllers,Requests,Resources}/
├── Models/
├── Providers/PersonsServiceProvider.php
├── Routes/api.php          # se publica en /api/persons
└── Services/               # lógica de negocio
```

Al arrancar, `ModuleRegistrationServiceProvider` recorre `app/Modules/*` y
registra cada `*ServiceProvider`. La clase base se ocupa de las rutas, las
migraciones, los nombres de ruta prefijados y la configuración del módulo, así
que **crear un módulo no obliga a tocar ningún archivo compartido**.

## Reglas de la casa

- Los módulos **no se importan entre sí**. Lo común va en `app/Support`.
- Los controladores solo traducen HTTP ↔ servicio; la lógica vive en `Services/`.
- Toda la validación pasa por Form Requests; toda la salida, por API Resources.
- Los borrados son lógicos (`SoftDeletes`) y se pueden restaurar.
- `declare(strict_types=1)` en todos los archivos nuevos.

## Comandos

Todos se ejecutan dentro del contenedor:

```bash
docker compose exec app php artisan migrate
```

```bash
docker compose exec app php artisan test
```

```bash
docker compose exec app php artisan route:list --path=api
```

```bash
docker compose exec app ./vendor/bin/pint
```

## Tests

Corren contra PostgreSQL, sobre la base de datos `<nombre>_testing` que crea el
contenedor `db` al inicializarse. `Tests\TestCase` fuerza ese cambio de base de
datos, de modo que `php artisan test` nunca puede tocar los datos de
desarrollo.

```
tests/Feature/
├── HealthTest.php
├── Persons/PersonApiTest.php
└── Vehicles/VehicleApiTest.php
```

## Documentación de cada módulo

- [`app/Modules/Persons/README.md`](app/Modules/Persons/README.md)
- [`app/Modules/Vehicles/README.md`](app/Modules/Vehicles/README.md)
