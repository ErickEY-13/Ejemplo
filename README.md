# Ejemplo — Laravel + Angular + PostgreSQL sobre Docker

Sistema modular con dos módulos independientes (**Registro de Personas** y
**Registro de Vehículos**), backend en Laravel expuesto como API REST y
frontend en Angular, todo dockerizado.

No necesitas tener instalado PHP, Node, Composer, Angular CLI ni PostgreSQL:
**solo Docker**.

---

## Índice

1. [Requisitos](#requisitos)
2. [Arranque rápido](#arranque-rápido)
3. [Qué levanta cada contenedor](#qué-levanta-cada-contenedor)
4. [Estructura del proyecto](#estructura-del-proyecto)
5. [Trabajo diario](#trabajo-diario)
6. [Arquitectura modular](#arquitectura-modular)
7. [Trabajo en equipo](#trabajo-en-equipo)
8. [API](#api)
9. [Despliegue en DigitalOcean](#despliegue-en-digitalocean)
10. [Actualizar el servidor](#actualizar-el-servidor)
11. [Problemas frecuentes](#problemas-frecuentes)

---

## Requisitos

- **Docker Desktop** (Windows/macOS) o **Docker Engine + Compose v2** (Linux).
- Git.

Comprueba que todo está en su sitio:

```bash
docker compose version
```

---

## Arranque rápido

```bash
git clone <url-del-repositorio> ejemplo
cd ejemplo
cp .env.example .env
docker compose up -d
```

En Windows, el `cp` desde PowerShell es `Copy-Item .env.example .env`.

La primera vez tarda unos minutos: se construyen las imágenes, se instalan las
dependencias de Composer y de npm, y se ejecutan las migraciones
(`AUTO_MIGRATE=true` viene activado en desarrollo).

Cuando termine:

| Servicio           | URL                                |
| ------------------ | ---------------------------------- |
| Aplicación Angular | <http://localhost:4200>            |
| API de Laravel     | <http://localhost:8000/api>        |
| Estado de la API   | <http://localhost:8000/api/health> |
| PostgreSQL         | `localhost:5432`                   |

Para seguir el avance de la instalación inicial:

```bash
docker compose logs -f
```

Datos de prueba (30 personas y 30 vehículos):

```bash
docker compose exec app php artisan db:seed
```

---

## Qué levanta cada contenedor

| Servicio   | Imagen                   | Función                                       |
| ---------- | ------------------------ | --------------------------------------------- |
| `db`       | `postgres:17-alpine`     | Base de datos, con volumen persistente         |
| `app`      | `docker/php/Dockerfile`  | Laravel sobre PHP-FPM 8.4                      |
| `nginx`    | `nginx:1.29-alpine`      | Publica la API en el puerto 8000               |
| `frontend` | `docker/node/Dockerfile` | `ng serve` con recarga en caliente en el 4200  |

Todos comparten la red `backend`. Los datos de PostgreSQL viven en el volumen
`db_data` y sobreviven a `docker compose down`.

El dev-server de Angular hace de **proxy** de `/api` hacia el contenedor
`nginx` (ver `frontend/proxy.conf.js`), así que el navegador siempre ve un único
origen — igual que en producción y sin necesidad de CORS.

En **producción** el reparto cambia: un único contenedor `web` (Nginx) sirve el
Angular ya compilado y hace de proxy hacia `app`, y se añaden `queue` y
`scheduler`. Ver [Despliegue](#despliegue-en-digitalocean).

---

## Estructura del proyecto

```
/
├── backend/                    # Laravel 13 (API REST)
│   ├── app/
│   │   ├── Modules/            # ◄── Un módulo por dominio de negocio
│   │   │   ├── Persons/
│   │   │   └── Vehicles/
│   │   ├── Providers/          # Autodescubrimiento de módulos
│   │   └── Support/Module/     # Clase base de los módulos
│   ├── config/modules.php      # Metadatos de los módulos
│   ├── routes/api.php          # Solo endpoints transversales
│   └── tests/Feature/          # Un directorio por módulo
│
├── frontend/                   # Angular 22 (SPA)
│   └── src/app/
│       ├── core/               # Cliente HTTP, interceptores, avisos
│       ├── shared/             # Componentes reutilizables
│       └── features/           # ◄── Un módulo por dominio de negocio
│           ├── home/           #     Menú principal
│           ├── persons/
│           └── vehicles/
│
├── docker/
│   ├── nginx/                  # Config de desarrollo y de producción
│   ├── node/                   # Imagen de desarrollo de Angular
│   ├── php/                    # Imagen de PHP-FPM (dev y prod)
│   ├── postgres/init/          # Extensiones y BD de tests
│   └── scripts/deploy.sh       # Despliegue en el servidor
│
├── docker-compose.yml          # Desarrollo
├── docker-compose.prod.yml     # Producción
├── .env.example
└── README.md
```

---

## Trabajo diario

Todos los comandos se ejecutan **dentro de los contenedores**; no hace falta
tener nada instalado en el equipo.

### Ciclo de vida

```bash
docker compose up -d          # Levantar
docker compose ps             # Ver estado
docker compose logs -f app    # Seguir los logs del backend
docker compose restart app    # Reiniciar un servicio
docker compose down           # Parar (los datos se conservan)
docker compose down -v        # Parar y BORRAR la base de datos
```

### Laravel (backend)

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan test
docker compose exec app php artisan route:list --path=api
docker compose exec app php artisan tinker
docker compose exec app composer require <paquete>
```

Formateo de código (Laravel Pint):

```bash
docker compose exec app ./vendor/bin/pint
```

### Angular (frontend)

```bash
docker compose exec frontend npm run build
docker compose exec frontend npm test
docker compose exec frontend npm install <paquete>
docker compose exec frontend npx ng generate component features/persons/pages/algo
```

Tras instalar un paquete de npm, reinicia el contenedor para que el dev-server
lo recoja:

```bash
docker compose restart frontend
```

### Base de datos

```bash
docker compose exec db psql -U ejemplo -d ejemplo
```

Copia de seguridad y restauración:

```bash
docker compose exec db pg_dump -U ejemplo ejemplo > backup.sql
```

```bash
docker compose exec -T db psql -U ejemplo -d ejemplo < backup.sql
```

Los tests usan una base de datos aparte (`ejemplo_testing`) que se crea sola al
inicializar el volumen, así que `php artisan test` nunca toca tus datos.

---

## Arquitectura modular

La idea central: **cada dominio de negocio es una carpeta autocontenida**, y
añadir uno nuevo no obliga a reorganizar nada.

### Backend

Un módulo es una carpeta en `backend/app/Modules/` con esta forma:

```
app/Modules/Persons/
├── Database/{Factories,Migrations,Seeders}/
├── Enums/
├── Http/{Controllers,Requests,Resources}/
├── Models/
├── Providers/PersonsServiceProvider.php
├── Routes/api.php
└── Services/
```

`App\Providers\ModuleRegistrationServiceProvider` recorre `app/Modules/*` y
registra cada `*ServiceProvider` que encuentra. Ese provider hereda de
`App\Support\Module\ModuleServiceProvider`, que se encarga sola de:

- publicar `Routes/api.php` bajo `/api/{prefijo}` con el middleware `api`;
- cargar las migraciones de `Database/Migrations`;
- prefijar los nombres de ruta (`persons.index`, `vehicles.index`);
- mezclar `Config/config.php` si existe.

`DatabaseSeeder` descubre los seeders de la misma manera.

**Consecuencia práctica:** crear un módulo nuevo no requiere tocar
`bootstrap/providers.php`, `routes/api.php` ni `DatabaseSeeder`.

<details>
<summary>Crear un módulo nuevo en el backend</summary>

```bash
docker compose exec app sh -c "mkdir -p app/Modules/Products/{Database/{Factories,Migrations,Seeders},Enums,Http/{Controllers,Requests,Resources},Models,Providers,Routes,Services}"
```

```php
// app/Modules/Products/Providers/ProductsServiceProvider.php
namespace App\Modules\Products\Providers;

use App\Support\Module\ModuleServiceProvider;

class ProductsServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Products';
    }
}
```

```php
// app/Modules/Products/Routes/api.php  ->  se publica en /api/products
use Illuminate\Support\Facades\Route;

Route::get('/', [ProductController::class, 'index'])->name('index');
```

Listo: `php artisan route:list` ya muestra las rutas nuevas.

</details>

### Frontend

Cada módulo es una carpeta en `frontend/src/app/features/` con sus propias
rutas, modelos, servicio y páginas. `app.routes.ts` los carga de forma
perezosa, así que el código de Vehículos no se descarga si el usuario solo
entra en Personas.

```ts
// app.routes.ts
{ path: 'personas',  loadChildren: () => import('./features/persons/persons.routes').then(m => m.PERSONS_ROUTES) },
{ path: 'vehiculos', loadChildren: () => import('./features/vehicles/vehicles.routes').then(m => m.VEHICLES_ROUTES) },
```

Para que un módulo aparezca en el menú principal basta con añadir una entrada
en `frontend/src/app/core/navigation/modules.ts`.

---

## Trabajo en equipo

El reparto está pensado para que dos personas trabajen en paralelo sin pisarse:

| Desarrollador   | Backend                  | Frontend                     |
| --------------- | ------------------------ | ---------------------------- |
| Desarrollador 1 | `app/Modules/Persons/`   | `src/app/features/persons/`  |
| Desarrollador 2 | `app/Modules/Vehicles/`  | `src/app/features/vehicles/` |

**Archivos compartidos** (avisar antes de tocarlos):

- `backend/routes/api.php`, `backend/config/modules.php`
- `backend/app/Support/`, `backend/app/Providers/`
- `frontend/src/app/core/`, `frontend/src/app/shared/`, `frontend/src/styles.scss`
- `frontend/src/app/app.routes.ts`, `frontend/src/app/core/navigation/modules.ts`
- `docker-compose*.yml`, `docker/`

Recomendaciones:

- Una rama por módulo: `feature/personas-...`, `feature/vehiculos-...`.
- Las migraciones de cada módulo van en su carpeta: dos personas creando
  migraciones a la vez no generan conflictos.
- Los módulos **no se importan entre sí**. Si dos módulos necesitan compartir
  algo, ese algo pertenece a `app/Support` (backend) o a `core`/`shared`
  (frontend), y se acuerda entre ambos.

---

## API

Base: `http://localhost:8000/api`

### Transversales

| Método | Ruta       | Descripción                                   |
| ------ | ---------- | --------------------------------------------- |
| `GET`  | `/health`  | Estado de la aplicación y de la base de datos  |
| `GET`  | `/modules` | Módulos disponibles                           |

### Módulos

Ambos exponen el mismo juego de endpoints REST:

| Método       | Ruta                      |
| ------------ | ------------------------- |
| `GET`        | `/{recurso}`              |
| `GET`        | `/{recurso}/metadata`     |
| `POST`       | `/{recurso}`              |
| `GET`        | `/{recurso}/{id}`         |
| `PUT\|PATCH` | `/{recurso}/{id}`         |
| `DELETE`     | `/{recurso}/{id}`         |
| `POST`       | `/{recurso}/{id}/restore` |

Donde `{recurso}` es `persons` o `vehicles`. El detalle de los filtros de cada
uno está en el README de su módulo:

- [`backend/app/Modules/Persons/README.md`](backend/app/Modules/Persons/README.md)
- [`backend/app/Modules/Vehicles/README.md`](backend/app/Modules/Vehicles/README.md)

Los borrados son **lógicos** (`deleted_at`): nada se pierde y todo se puede
restaurar.

---

## Despliegue en DigitalOcean

### 1. Preparar el Droplet

Un Droplet Ubuntu 22.04/24.04 con 2 GB de RAM es suficiente para empezar.

```bash
ssh root@TU_IP
```

```bash
curl -fsSL https://get.docker.com | sh
```

```bash
adduser --disabled-password --gecos "" deploy && usermod -aG docker deploy
```

```bash
ufw allow OpenSSH && ufw allow 80 && ufw allow 443 && ufw --force enable
```

Si el Droplet tiene 1 GB de RAM, añade swap antes de construir las imágenes:

```bash
fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
```

### 2. Clonar y configurar

```bash
su - deploy && git clone <url-del-repositorio> app && cd app && cp .env.example .env
```

Genera la clave de la aplicación:

```bash
docker compose run --rm app php artisan key:generate --show
```

Edita `.env` con esos valores:

```ini
COMPOSE_FILE=docker-compose.prod.yml     # ◄── imprescindible

APP_NAME=Ejemplo
APP_URL=https://tu-dominio.com
FRONTEND_URL=https://tu-dominio.com
APP_KEY=base64:...                        # la que acabas de generar

DB_DATABASE=ejemplo
DB_USERNAME=ejemplo
DB_PASSWORD=<una-contraseña-larga-y-aleatoria>

HTTP_PORT=80
LOG_LEVEL=warning
AUTO_MIGRATE=false
```

`COMPOSE_FILE` hace que `docker compose` use el archivo de producción sin tener
que pasarle `-f` en cada comando.

### 3. Levantar

```bash
docker compose up -d --build
```

```bash
docker compose exec app php artisan migrate --force
```

La aplicación queda disponible en `http://TU_IP`. En producción, Angular y la
API se sirven desde el mismo origen: `/` es el SPA y `/api` es Laravel.

### 4. HTTPS

Lo más sencillo es poner Caddy o Nginx delante como proxy inverso, o usar un
Load Balancer de DigitalOcean con certificado gestionado. Si terminas TLS por
delante, cambia `HTTP_PORT` a un puerto interno (por ejemplo `8080`) y apunta
el proxy ahí.

---

## Actualizar el servidor

```bash
git pull
```

```bash
docker compose up -d --build
```

```bash
docker compose exec app php artisan migrate --force
```

Las dependencias **no** se instalan a mano: la imagen de producción ejecuta
`composer install` y `npm ci` durante el `--build`, y el entrypoint vuelve a
cachear configuración y rutas al arrancar.

También puedes usar el script incluido, que hace lo anterior y además comprueba
que la aplicación responde:

```bash
./docker/scripts/deploy.sh
```

En desarrollo el flujo es aún más corto: los contenedores detectan por sí solos
que `composer.lock` o `package-lock.json` han cambiado y reinstalan lo que haga
falta al arrancar.

```bash
git pull && docker compose up -d --build
```

---

## Problemas frecuentes

<details>
<summary><b>El puerto 8000, 4200 o 5432 ya está ocupado</b></summary>

Cambia los puertos en `.env` y vuelve a levantar:

```ini
API_PORT=8001
FRONTEND_PORT=4300
DB_PORT_HOST=5433
```

```bash
docker compose up -d
```

</details>

<details>
<summary><b>Angular tarda mucho la primera vez</b></summary>

Es normal: está instalando `node_modules` dentro de un volumen. Sigue el avance
con `docker compose logs -f frontend`. Las siguientes veces arranca en segundos.

</details>

<details>
<summary><b>El frontend no ve los cambios que guardo</b></summary>

El contenedor usa *polling* para detectar cambios (necesario en Windows y
macOS). Si aun así no reacciona:

```bash
docker compose restart frontend
```

</details>

<details>
<summary><b>«No se pudo conectar con el servidor» en la interfaz</b></summary>

Comprueba que el backend responde:

```bash
curl http://localhost:8000/api/health
```

```bash
docker compose logs app --tail 50
```

</details>

<details>
<summary><b>Errores de permisos en Linux</b></summary>

Ajusta `UID`/`GID` en `.env` con tus valores reales (`id -u` e `id -g`) y
reconstruye:

```bash
docker compose up -d --build app
```

En Windows y macOS no hace falta: el contenedor lo detecta y se adapta solo.

</details>

<details>
<summary><b>Empezar de cero</b></summary>

```bash
docker compose down -v && docker compose up -d --build
```

```bash
docker compose exec app php artisan migrate --seed
```

`-v` borra también la base de datos.

</details>
