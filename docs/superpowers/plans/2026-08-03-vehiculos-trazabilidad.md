# Trazabilidad de Vehículos (sede, traslados, responsable histórico) — Plan de Implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reemplazar el modelo actual de asignación de vehículos ("estado único que se sobrescribe") por un historial inmutable de vehículo + sede + responsable con fechas de inicio/fin, y exponerlo en el frontend (listado con sede/responsable actual + filtro, historial por vehículo).

**Architecture:** Backend Laravel modular (`app/Modules/Assignments`, `app/Modules/Vehicles`); el módulo `Assignments` sigue siendo el único puente entre `Vehicles` y `Persons`. Se añade una tabla `sites` y se transforma `vehicle_assignments` de "una fila por vehículo, se sobrescribe" a "N filas por vehículo, `ended_at IS NULL` = vigente". Frontend Angular standalone components + signals; `features/assignments` sigue siendo el único módulo frontend que conoce tanto a `features/vehicles` como a `features/persons`.

**Tech Stack:** Laravel 13 / PHP 8.3 / PostgreSQL (dev y test, vía Docker) — Angular (standalone components, signals) / PrimeNG.

## Global Constraints

- No se modifica el módulo `Persons` ni su enum `App\Modules\Persons\Enums\Site` (spec, sección "Fuera de alcance").
- No se conecta este flujo al sistema genérico `Audit` (spec, sección "Fuera de alcance" y decisión de diseño 7).
- No se agregan restricciones de rol/permiso nuevas (decisión de diseño 5).
- No se permite backdatar manualmente `started_at`; siempre es `now()` (spec, sección "Fuera de alcance").
- Las sedes iniciales deben coincidir en `code` con los valores del enum `Site` de `Person`: `main`, `north`, `south`, `east`, `west`, `annex` (decisión de diseño 2).
- Base de datos: PostgreSQL tanto en desarrollo como en tests (`backend/tests/TestCase.php` fuerza `_testing`); evitar añadir `doctrine/dbal` como dependencia — usar SQL nativo (`DB::statement`) para alterar la nulabilidad de una columna existente.
- El frontend no tiene tests de componentes para páginas/features (solo `api.service.spec.ts` y `server-errors.spec.ts`); no se introduce ese patrón aquí. La verificación de UI es manual, en el navegador, con `docker compose exec frontend` ya corriendo (o `ng serve`).
- Comandos backend: `docker compose exec app php artisan migrate`, `docker compose exec app php artisan test --filter=<Nombre>`.

---

## Task 1: Tabla `sites` — modelo, migración y seeder

**Files:**
- Create: `backend/app/Modules/Assignments/Database/Migrations/2026_01_01_000302_create_sites_table.php`
- Create: `backend/app/Modules/Assignments/Models/Site.php`
- Create: `backend/app/Modules/Assignments/Database/Seeders/SitesSeeder.php`
- Test: `backend/tests/Feature/Assignments/SiteModelTest.php`

**Interfaces:**
- Produces: `App\Modules\Assignments\Models\Site` (Eloquent model, tabla `sites`, columnas `id`, `code` string único, `name` string, timestamps). `App\Modules\Assignments\Database\Seeders\SitesSeeder` (seeder autodescubierto por `DatabaseSeeder`, crea 6 sedes si la tabla está vacía).

- [ ] **Step 1: Escribir los tests que fallan**

`backend/tests/Feature/Assignments/SiteModelTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Assignments;

use App\Modules\Assignments\Database\Seeders\SitesSeeder;
use App\Modules\Assignments\Models\Site;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SiteModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function crea_una_sede_con_codigo_unico(): void
    {
        Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);

        $this->assertDatabaseHas('sites', ['code' => 'main', 'name' => 'Sede Central']);
    }

    #[Test]
    public function no_permite_dos_sedes_con_el_mismo_codigo(): void
    {
        Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);

        $this->expectException(QueryException::class);

        Site::query()->create(['code' => 'main', 'name' => 'Otra sede']);
    }

    #[Test]
    public function el_seeder_crea_las_seis_sedes_iniciales(): void
    {
        $this->seed(SitesSeeder::class);

        $this->assertDatabaseCount('sites', 6);
        $this->assertDatabaseHas('sites', ['code' => 'main']);
        $this->assertDatabaseHas('sites', ['code' => 'annex']);
    }
}
```

- [ ] **Step 2: Ejecutar y verificar que falla**

Run: `docker compose exec app php artisan test --filter=SiteModelTest`
Expected: FAIL — la tabla `sites` no existe / la clase `Site` no existe.

- [ ] **Step 3: Crear la migración**

`backend/app/Modules/Assignments/Database/Migrations/2026_01_01_000302_create_sites_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name', 80);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
```

- [ ] **Step 4: Crear el modelo**

`backend/app/Modules/Assignments/Models/Site.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Assignments\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Sede física donde puede estar un vehículo. Tabla propia, independiente del
 * enum `App\Modules\Persons\Enums\Site`: los códigos iniciales coinciden a
 * propósito, pero cada uno se administra por separado.
 *
 * @property int $id
 * @property string $code
 * @property string $name
 */
class Site extends Model
{
    protected $fillable = ['code', 'name'];
}
```

- [ ] **Step 5: Crear el seeder**

`backend/app/Modules/Assignments/Database/Seeders/SitesSeeder.php`:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Assignments\Database\Seeders;

use App\Modules\Assignments\Models\Site;
use Illuminate\Database\Seeder;

/**
 * Lo ejecuta automáticamente Database\Seeders\DatabaseSeeder.
 *
 * Los códigos coinciden a propósito con App\Modules\Persons\Enums\Site.
 */
class SitesSeeder extends Seeder
{
    /**
     * @var list<array{code: string, name: string}>
     */
    private const SITES = [
        ['code' => 'main', 'name' => 'Sede Central'],
        ['code' => 'north', 'name' => 'Sede Norte'],
        ['code' => 'south', 'name' => 'Sede Sur'],
        ['code' => 'east', 'name' => 'Sede Este'],
        ['code' => 'west', 'name' => 'Sede Oeste'],
        ['code' => 'annex', 'name' => 'Anexo Municipal'],
    ];

    public function run(): void
    {
        if (Site::query()->exists()) {
            $this->command?->info('Módulo Assignments: ya hay sedes, se omite el seeder.');

            return;
        }

        foreach (self::SITES as $site) {
            Site::query()->create($site);
        }

        $this->command?->info('Módulo Assignments: '.count(self::SITES).' sedes creadas.');
    }
}
```

- [ ] **Step 6: Ejecutar migraciones y tests, verificar que pasan**

Run:
```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan test --filter=SiteModelTest
```
Expected: PASS (3 tests).

- [ ] **Step 7: Commit**

```bash
git add backend/app/Modules/Assignments/Database/Migrations/2026_01_01_000302_create_sites_table.php \
        backend/app/Modules/Assignments/Models/Site.php \
        backend/app/Modules/Assignments/Database/Seeders/SitesSeeder.php \
        backend/tests/Feature/Assignments/SiteModelTest.php
git commit -m "feat(assignments): agrega tabla y modelo Site para sedes de vehículos"
```

---

## Task 2: Migración de `vehicle_assignments` para soportar historial

**Files:**
- Create: `backend/app/Modules/Assignments/Database/Migrations/2026_01_01_000303_add_site_and_history_to_vehicle_assignments_table.php`
- Test: `backend/tests/Feature/Assignments/VehicleAssignmentsSchemaTest.php`

**Interfaces:**
- Consumes: tabla `sites` (Task 1).
- Produces: columnas `vehicle_assignments.site_id` (FK a `sites`, nullable), `vehicle_assignments.ended_at` (timestamp, nullable), `person_id` ahora nullable, sin índice único en `vehicle_id`, índice compuesto `(vehicle_id, ended_at)`.

- [ ] **Step 1: Escribir los tests que fallan**

`backend/tests/Feature/Assignments/VehicleAssignmentsSchemaTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Assignments;

use App\Modules\Assignments\Models\Site;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VehicleAssignmentsSchemaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function permite_varias_filas_de_asignacion_para_el_mismo_vehiculo(): void
    {
        $vehicle = Vehicle::factory()->create();
        $site = Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);

        DB::table('vehicle_assignments')->insert([
            'vehicle_id' => $vehicle->id,
            'site_id' => $site->id,
            'person_id' => null,
            'assigned_at' => now()->subDay(),
            'ended_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('vehicle_assignments')->insert([
            'vehicle_id' => $vehicle->id,
            'site_id' => $site->id,
            'person_id' => null,
            'assigned_at' => now(),
            'ended_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseCount('vehicle_assignments', 2);
    }

    #[Test]
    public function permite_una_asignacion_sin_responsable(): void
    {
        $vehicle = Vehicle::factory()->create();
        $site = Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);

        DB::table('vehicle_assignments')->insert([
            'vehicle_id' => $vehicle->id,
            'site_id' => $site->id,
            'person_id' => null,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('vehicle_assignments', [
            'vehicle_id' => $vehicle->id,
            'person_id' => null,
        ]);
    }
}
```

- [ ] **Step 2: Ejecutar y verificar que falla**

Run: `docker compose exec app php artisan test --filter=VehicleAssignmentsSchemaTest`
Expected: FAIL — el primer test choca con el índice único de `vehicle_id`; el segundo, con el `NOT NULL` de `person_id`.

- [ ] **Step 3: Crear la migración**

`backend/app/Modules/Assignments/Database/Migrations/2026_01_01_000303_add_site_and_history_to_vehicle_assignments_table.php`:

```php
<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vehicle_assignments', function (Blueprint $table) {
            $table->dropUnique(['vehicle_id']);

            $table->foreignId('site_id')->nullable()->after('vehicle_id')->constrained('sites')->nullOnDelete();
            $table->timestamp('ended_at')->nullable()->after('assigned_at');

            $table->index(['vehicle_id', 'ended_at']);
        });

        // `person_id` deja de ser obligatorio: un vehículo puede quedar en una
        // sede sin responsable. SQL nativo para no añadir doctrine/dbal solo
        // para este `change()`.
        DB::statement('ALTER TABLE vehicle_assignments ALTER COLUMN person_id DROP NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE vehicle_assignments ALTER COLUMN person_id SET NOT NULL');

        Schema::table('vehicle_assignments', function (Blueprint $table) {
            $table->dropIndex(['vehicle_id', 'ended_at']);
            $table->dropColumn('ended_at');
            $table->dropConstrainedForeignId('site_id');
        });

        Schema::table('vehicle_assignments', function (Blueprint $table) {
            $table->unique('vehicle_id');
        });
    }
};
```

- [ ] **Step 4: Ejecutar migraciones y tests, verificar que pasan**

Run:
```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan test --filter=VehicleAssignmentsSchemaTest
```
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add backend/app/Modules/Assignments/Database/Migrations/2026_01_01_000303_add_site_and_history_to_vehicle_assignments_table.php \
        backend/tests/Feature/Assignments/VehicleAssignmentsSchemaTest.php
git commit -m "feat(assignments): permite historial real en vehicle_assignments (sede, sin unique, responsable opcional)"
```

---

## Task 3: Modelo `VehicleAssignment` actualizado

**Files:**
- Modify: `backend/app/Modules/Assignments/Models/VehicleAssignment.php`
- Test: `backend/tests/Feature/Assignments/VehicleAssignmentModelTest.php`

**Interfaces:**
- Consumes: `App\Modules\Assignments\Models\Site` (Task 1).
- Produces: `VehicleAssignment::site(): BelongsTo`, `VehicleAssignment::isActive(): bool`, `VehicleAssignment::isOverdue(): bool` (ahora `false` si `ended_at !== null`), fillable incluye `site_id` y `ended_at`.

- [ ] **Step 1: Escribir los tests que fallan**

`backend/tests/Feature/Assignments/VehicleAssignmentModelTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Assignments;

use App\Modules\Assignments\Models\Site;
use App\Modules\Assignments\Models\VehicleAssignment;
use App\Modules\Persons\Models\Person;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VehicleAssignmentModelTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function carga_la_sede_relacionada(): void
    {
        $vehicle = Vehicle::factory()->create();
        $site = Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);

        $assignment = VehicleAssignment::query()->create([
            'vehicle_id' => $vehicle->id,
            'site_id' => $site->id,
            'assigned_at' => now(),
        ]);

        $this->assertSame('Sede Central', $assignment->site->name);
    }

    #[Test]
    public function permite_una_asignacion_sin_persona(): void
    {
        $vehicle = Vehicle::factory()->create();
        $site = Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);

        $assignment = VehicleAssignment::query()->create([
            'vehicle_id' => $vehicle->id,
            'site_id' => $site->id,
            'assigned_at' => now(),
        ]);

        $this->assertNull($assignment->person);
        $this->assertTrue($assignment->isActive());
    }

    #[Test]
    public function una_asignacion_cerrada_no_se_considera_atrasada(): void
    {
        $vehicle = Vehicle::factory()->create();
        $person = Person::factory()->create();
        $site = Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);

        $assignment = VehicleAssignment::query()->create([
            'vehicle_id' => $vehicle->id,
            'site_id' => $site->id,
            'person_id' => $person->id,
            'assigned_at' => Carbon::now()->subDays(10),
            'ended_at' => Carbon::now()->subDays(1),
            'expected_return_at' => Carbon::now()->subDays(3)->toDateString(),
        ]);

        $this->assertFalse($assignment->isActive());
        $this->assertFalse($assignment->isOverdue());
    }
}
```

- [ ] **Step 2: Ejecutar y verificar que falla**

Run: `docker compose exec app php artisan test --filter=VehicleAssignmentModelTest`
Expected: FAIL — `site_id`/`ended_at` no están en `$fillable`, `site()` e `isActive()` no existen.

- [ ] **Step 3: Actualizar el modelo**

`backend/app/Modules/Assignments/Models/VehicleAssignment.php` (reemplaza el archivo completo):

```php
<?php

declare(strict_types=1);

namespace App\Modules\Assignments\Models;

use App\Modules\Persons\Models\Person;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Vincula un vehículo con su sede y, opcionalmente, con la persona
 * responsable de él en un momento dado.
 *
 * Cada fila es un registro histórico inmutable: `ended_at = null` significa
 * que sigue vigente. Reasignar (o trasladar de sede) no modifica la fila
 * activa, la cierra y crea una nueva — así se conserva la trazabilidad
 * completa de dónde estuvo un vehículo y quién lo tuvo a cargo.
 *
 * Este módulo es el único puente entre Vehículos y Personas: ninguno de los
 * dos importa clases del otro, pero ambos son libres de ser referenciados
 * desde aquí.
 *
 * @property int $id
 * @property int $vehicle_id
 * @property int|null $site_id
 * @property int|null $person_id
 * @property \Illuminate\Support\Carbon $assigned_at
 * @property \Illuminate\Support\Carbon|null $ended_at
 * @property \Illuminate\Support\Carbon|null $expected_return_at
 * @property string|null $notes
 */
class VehicleAssignment extends Model
{
    protected $fillable = [
        'vehicle_id',
        'site_id',
        'person_id',
        'assigned_at',
        'ended_at',
        'expected_return_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'ended_at' => 'datetime',
            'expected_return_at' => 'date',
        ];
    }

    /**
     * Sigue vigente (no ha sido cerrada por un traslado o una baja).
     */
    public function isActive(): bool
    {
        return $this->ended_at === null;
    }

    /**
     * La devolución prevista ya pasó y la asignación sigue vigente.
     */
    public function isOverdue(): bool
    {
        return $this->isActive()
            && $this->expected_return_at !== null
            && $this->expected_return_at->isPast();
    }

    /**
     * @return BelongsTo<Vehicle, $this>
     */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * `withTrashed`: si la persona se elimina más adelante, la asignación
     * sigue mostrando quién es, en vez de romperse.
     *
     * @return BelongsTo<Person, $this>
     */
    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class)->withTrashed();
    }

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
```

- [ ] **Step 4: Ejecutar y verificar que pasa**

Run: `docker compose exec app php artisan test --filter=VehicleAssignmentModelTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Ejecutar toda la suite de Assignments para detectar regresiones**

Run: `docker compose exec app php artisan test --filter=Assignment`
Expected: los tests antiguos de `AssignmentApiTest` pueden fallar en este punto (esperado: se reescriben en el Task 5). Confirma que no hay errores fatales de sintaxis/autoload.

- [ ] **Step 6: Commit**

```bash
git add backend/app/Modules/Assignments/Models/VehicleAssignment.php \
        backend/tests/Feature/Assignments/VehicleAssignmentModelTest.php
git commit -m "feat(assignments): VehicleAssignment soporta sede y responsable opcional"
```

---

## Task 4: `AssignmentService` — reescritura para historial real

**Files:**
- Modify: `backend/app/Modules/Assignments/Services/AssignmentService.php`
- Test: `backend/tests/Feature/Assignments/AssignmentServiceTest.php`

**Interfaces:**
- Consumes: `VehicleAssignment` (Task 3), `Site` (Task 1).
- Produces: `AssignmentService::current(Vehicle): ?VehicleAssignment`, `::currentAll(?int $siteId = null): Collection`, `::history(Vehicle): Collection`, `::assign(Vehicle, int $siteId, ?int $personId, ?string $expectedReturnAt, ?string $notes): VehicleAssignment`, `::unassign(Vehicle): void`, `::sites(): Collection`, `::searchPeople(?string): array` (sin cambios). Estas firmas las consume el Task 5 (`AssignmentController`).

- [ ] **Step 1: Escribir los tests que fallan**

`backend/tests/Feature/Assignments/AssignmentServiceTest.php`:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Assignments;

use App\Modules\Assignments\Models\Site;
use App\Modules\Assignments\Services\AssignmentService;
use App\Modules\Persons\Models\Person;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssignmentServiceTest extends TestCase
{
    use RefreshDatabase;

    private AssignmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AssignmentService::class);
    }

    #[Test]
    public function asignar_crea_una_fila_con_sede_y_responsable(): void
    {
        $vehicle = Vehicle::factory()->create();
        $person = Person::factory()->create();
        $site = Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);

        $assignment = $this->service->assign($vehicle, $site->id, $person->id, null, null);

        $this->assertSame($site->id, $assignment->site_id);
        $this->assertSame($person->id, $assignment->person_id);
        $this->assertNull($assignment->ended_at);
    }

    #[Test]
    public function asignar_de_nuevo_cierra_la_anterior_y_conserva_el_historial(): void
    {
        $vehicle = Vehicle::factory()->create();
        $siteA = Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);
        $siteB = Site::query()->create(['code' => 'north', 'name' => 'Sede Norte']);
        $person = Person::factory()->create();

        $first = $this->service->assign($vehicle, $siteA->id, $person->id, null, null);
        $second = $this->service->assign($vehicle, $siteB->id, $person->id, null, null);

        $this->assertNotNull($first->refresh()->ended_at);
        $this->assertNull($second->ended_at);
        $this->assertDatabaseCount('vehicle_assignments', 2);
    }

    #[Test]
    public function asignar_sin_persona_deja_el_vehiculo_en_una_sede_sin_responsable(): void
    {
        $vehicle = Vehicle::factory()->create();
        $site = Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);

        $assignment = $this->service->assign($vehicle, $site->id, null, null, null);

        $this->assertNull($assignment->person_id);
        $this->assertSame($site->id, $assignment->site_id);
    }

    #[Test]
    public function quitar_la_asignacion_cierra_sin_borrar_la_fila(): void
    {
        $vehicle = Vehicle::factory()->create();
        $site = Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);
        $person = Person::factory()->create();

        $assignment = $this->service->assign($vehicle, $site->id, $person->id, null, null);

        $this->service->unassign($vehicle);

        $this->assertDatabaseCount('vehicle_assignments', 1);
        $this->assertNotNull($assignment->refresh()->ended_at);
        $this->assertNull($this->service->current($vehicle));
    }

    #[Test]
    public function el_historial_devuelve_las_asignaciones_de_mas_reciente_a_mas_antigua(): void
    {
        $vehicle = Vehicle::factory()->create();
        $siteA = Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);
        $siteB = Site::query()->create(['code' => 'north', 'name' => 'Sede Norte']);

        $first = $this->service->assign($vehicle, $siteA->id, null, null, null);
        $second = $this->service->assign($vehicle, $siteB->id, null, null, null);

        $history = $this->service->history($vehicle);

        $this->assertCount(2, $history);
        $this->assertSame($second->id, $history->first()->id);
        $this->assertSame($first->id, $history->last()->id);
    }

    #[Test]
    public function current_all_filtra_por_sede(): void
    {
        $vehicleA = Vehicle::factory()->create();
        $vehicleB = Vehicle::factory()->create();
        $siteA = Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);
        $siteB = Site::query()->create(['code' => 'north', 'name' => 'Sede Norte']);

        $this->service->assign($vehicleA, $siteA->id, null, null, null);
        $this->service->assign($vehicleB, $siteB->id, null, null, null);

        $this->assertCount(2, $this->service->currentAll());
        $this->assertCount(1, $this->service->currentAll($siteA->id));
    }

    #[Test]
    public function sites_devuelve_las_sedes_ordenadas_por_nombre(): void
    {
        Site::query()->create(['code' => 'north', 'name' => 'Sede Norte']);
        Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);

        $names = $this->service->sites()->pluck('name')->all();

        $this->assertSame(['Sede Central', 'Sede Norte'], $names);
    }
}
```

- [ ] **Step 2: Ejecutar y verificar que falla**

Run: `docker compose exec app php artisan test --filter=AssignmentServiceTest`
Expected: FAIL — `assign()`/`current()` no aceptan `$siteId`; `currentAll()`, `history()`, `sites()` no existen.

- [ ] **Step 3: Reescribir el servicio**

`backend/app/Modules/Assignments/Services/AssignmentService.php` (reemplaza el archivo completo):

```php
<?php

declare(strict_types=1);

namespace App\Modules\Assignments\Services;

use App\Modules\Assignments\Models\Site;
use App\Modules\Assignments\Models\VehicleAssignment;
use App\Modules\Persons\Models\Person;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Lógica de negocio del módulo de Assignments.
 *
 * Único módulo que conoce tanto a Vehículos como a Personas; ninguno de los
 * dos debería importar clases del otro directamente.
 */
class AssignmentService
{
    public function current(Vehicle $vehicle): ?VehicleAssignment
    {
        return VehicleAssignment::query()
            ->where('vehicle_id', $vehicle->id)
            ->whereNull('ended_at')
            ->first();
    }

    /**
     * @return Collection<int, VehicleAssignment>
     */
    public function currentAll(?int $siteId = null): Collection
    {
        return VehicleAssignment::query()
            ->whereNull('ended_at')
            ->with(['person', 'site'])
            ->when($siteId !== null, fn (Builder $query) => $query->where('site_id', $siteId))
            ->get();
    }

    /**
     * @return Collection<int, VehicleAssignment>
     */
    public function history(Vehicle $vehicle): Collection
    {
        return VehicleAssignment::query()
            ->where('vehicle_id', $vehicle->id)
            ->with(['person', 'site'])
            ->orderByDesc('assigned_at')
            ->get();
    }

    /**
     * Traslada/(re)asigna el vehículo: cierra la asignación activa (si la
     * hay) y crea una nueva. Es la única forma de escribir en
     * `vehicle_assignments`, así que el historial queda completo por
     * construcción.
     */
    public function assign(Vehicle $vehicle, int $siteId, ?int $personId, ?string $expectedReturnAt, ?string $notes): VehicleAssignment
    {
        return DB::transaction(function () use ($vehicle, $siteId, $personId, $expectedReturnAt, $notes) {
            $this->current($vehicle)?->update(['ended_at' => now()]);

            return VehicleAssignment::query()->create([
                'vehicle_id' => $vehicle->id,
                'site_id' => $siteId,
                'person_id' => $personId,
                'assigned_at' => now(),
                'expected_return_at' => $expectedReturnAt,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Cierra la asignación activa del vehículo, si la hay. No borra el
     * historial: solo deja de estar vigente.
     */
    public function unassign(Vehicle $vehicle): void
    {
        $this->current($vehicle)?->update(['ended_at' => now()]);
    }

    /**
     * @return Collection<int, Site>
     */
    public function sites(): Collection
    {
        return Site::query()->orderBy('name')->get();
    }

    /**
     * Búsqueda liviana de personas activas para el selector del frontend.
     * Vive aquí (y no en Personas) para que Vehículos nunca tenga que
     * importar el servicio de Personas.
     *
     * @return list<array<string, mixed>>
     */
    public function searchPeople(?string $term): array
    {
        return Person::query()
            ->active()
            ->search($term)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->limit(20)
            ->get(['id', 'first_name', 'last_name', 'document_number', 'site'])
            ->map(fn (Person $person): array => [
                'id' => $person->id,
                'full_name' => $person->full_name,
                'document_number' => $person->document_number,
                'site' => $person->site,
            ])
            ->all();
    }
}
```

- [ ] **Step 4: Ejecutar y verificar que pasa**

Run: `docker compose exec app php artisan test --filter=AssignmentServiceTest`
Expected: PASS (7 tests).

- [ ] **Step 5: Commit**

```bash
git add backend/app/Modules/Assignments/Services/AssignmentService.php \
        backend/tests/Feature/Assignments/AssignmentServiceTest.php
git commit -m "feat(assignments): AssignmentService gestiona historial de sede y responsable"
```

---

## Task 5: HTTP — Request, Resource, Controller, rutas y tests de API

**Files:**
- Modify: `backend/app/Modules/Assignments/Http/Requests/AssignVehicleRequest.php`
- Modify: `backend/app/Modules/Assignments/Http/Resources/VehicleAssignmentResource.php`
- Modify: `backend/app/Modules/Assignments/Http/Controllers/AssignmentController.php`
- Modify: `backend/app/Modules/Assignments/Routes/api.php`
- Modify: `backend/tests/Feature/Assignments/AssignmentApiTest.php` (reescritura completa)
- Modify: `backend/app/Modules/Assignments/README.md`

**Interfaces:**
- Consumes: `AssignmentService` (Task 4).
- Produces contrato JSON (lo consume el Task 6, frontend):
  - `GET /api/assignments?site_id=` → `{data: VehicleAssignmentResource[]}`
  - `GET /api/assignments/sites` → `{data: {id, code, name}[]}`
  - `GET /api/assignments/people?search=` → sin cambios
  - `GET /api/assignments/{vehicle}` → `{data: VehicleAssignmentResource | null}`
  - `GET /api/assignments/{vehicle}/history` → `{data: VehicleAssignmentResource[]}`
  - `PUT|PATCH /api/assignments/{vehicle}` body `{site_id (required), person_id?, expected_return_at?, notes?}` → `VehicleAssignmentResource`
  - `DELETE /api/assignments/{vehicle}` → 204, cierra en vez de borrar
  - `VehicleAssignmentResource` → `{id, vehicle_id, site: {id,code,name}|null, person: {...}|null, notes, assigned_at, ended_at, expected_return_at, is_overdue}`

- [ ] **Step 1: Reescribir los tests de API (fallarán hasta el Step 5)**

`backend/tests/Feature/Assignments/AssignmentApiTest.php` (reemplaza el archivo completo):

```php
<?php

declare(strict_types=1);

namespace Tests\Feature\Assignments;

use App\Modules\Assignments\Models\Site;
use App\Modules\Assignments\Models\VehicleAssignment;
use App\Modules\Persons\Models\Person;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssignmentApiTest extends TestCase
{
    use RefreshDatabase;

    private function createSite(string $code = 'main', string $name = 'Sede Central'): Site
    {
        return Site::query()->create(['code' => $code, 'name' => $name]);
    }

    #[Test]
    public function un_vehiculo_sin_asignar_devuelve_null(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->getJson("/api/assignments/{$vehicle->id}")
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    #[Test]
    public function asigna_un_vehiculo_a_una_persona_en_una_sede(): void
    {
        $vehicle = Vehicle::factory()->create();
        $person = Person::factory()->create(['site' => 'main']);
        $site = $this->createSite();

        $this->putJson("/api/assignments/{$vehicle->id}", [
            'site_id' => $site->id,
            'person_id' => $person->id,
            'expected_return_at' => now()->addDays(3)->toDateString(),
            'notes' => 'Uso exclusivo en horario laboral.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.person.id', $person->id)
            ->assertJsonPath('data.person.full_name', $person->full_name)
            ->assertJsonPath('data.site.id', $site->id)
            ->assertJsonPath('data.site.name', 'Sede Central')
            ->assertJsonPath('data.expected_return_at', now()->addDays(3)->toDateString())
            ->assertJsonPath('data.is_overdue', false)
            ->assertJsonPath('data.notes', 'Uso exclusivo en horario laboral.');

        $this->assertDatabaseHas('vehicle_assignments', [
            'vehicle_id' => $vehicle->id,
            'person_id' => $person->id,
            'site_id' => $site->id,
        ]);

        $this->getJson("/api/assignments/{$vehicle->id}")
            ->assertOk()
            ->assertJsonPath('data.person.id', $person->id);
    }

    #[Test]
    public function asigna_una_sede_sin_responsable(): void
    {
        $vehicle = Vehicle::factory()->create();
        $site = $this->createSite();

        $this->putJson("/api/assignments/{$vehicle->id}", ['site_id' => $site->id])
            ->assertCreated()
            ->assertJsonPath('data.site.id', $site->id)
            ->assertJsonPath('data.person', null);
    }

    #[Test]
    public function reasignar_cierra_la_asignacion_anterior_y_conserva_el_historial(): void
    {
        $vehicle = Vehicle::factory()->create();
        $siteA = $this->createSite('main', 'Sede Central');
        $siteB = $this->createSite('north', 'Sede Norte');
        $primera = Person::factory()->create();
        $segunda = Person::factory()->create();

        $this->putJson("/api/assignments/{$vehicle->id}", ['site_id' => $siteA->id, 'person_id' => $primera->id])
            ->assertCreated();
        $this->putJson("/api/assignments/{$vehicle->id}", ['site_id' => $siteB->id, 'person_id' => $segunda->id])
            ->assertOk()
            ->assertJsonPath('data.person.id', $segunda->id)
            ->assertJsonPath('data.site.id', $siteB->id);

        $this->assertDatabaseCount('vehicle_assignments', 2);

        $primeraFila = VehicleAssignment::query()->where('person_id', $primera->id)->firstOrFail();
        $this->assertNotNull($primeraFila->ended_at);
    }

    #[Test]
    public function rechaza_una_asignacion_sin_sede(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->putJson("/api/assignments/{$vehicle->id}", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('site_id');
    }

    #[Test]
    public function rechaza_una_persona_inexistente(): void
    {
        $vehicle = Vehicle::factory()->create();
        $site = $this->createSite();

        $this->putJson("/api/assignments/{$vehicle->id}", ['site_id' => $site->id, 'person_id' => 999999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('person_id');
    }

    #[Test]
    public function rechaza_una_persona_eliminada(): void
    {
        $vehicle = Vehicle::factory()->create();
        $site = $this->createSite();
        $person = Person::factory()->create();
        $person->delete();

        $this->putJson("/api/assignments/{$vehicle->id}", ['site_id' => $site->id, 'person_id' => $person->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('person_id');
    }

    #[Test]
    public function quita_la_asignacion_cerrandola_sin_borrarla(): void
    {
        $vehicle = Vehicle::factory()->create();
        $site = $this->createSite();
        $person = Person::factory()->create();

        $this->putJson("/api/assignments/{$vehicle->id}", ['site_id' => $site->id, 'person_id' => $person->id])
            ->assertCreated();

        $this->deleteJson("/api/assignments/{$vehicle->id}")->assertNoContent();

        $this->assertDatabaseCount('vehicle_assignments', 1);

        $this->getJson("/api/assignments/{$vehicle->id}")
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    #[Test]
    public function quitar_una_asignacion_inexistente_no_falla(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->deleteJson("/api/assignments/{$vehicle->id}")->assertNoContent();
    }

    #[Test]
    public function muestra_a_la_persona_eliminada_en_una_asignacion_existente(): void
    {
        $vehicle = Vehicle::factory()->create();
        $site = $this->createSite();
        $person = Person::factory()->create();

        $this->putJson("/api/assignments/{$vehicle->id}", ['site_id' => $site->id, 'person_id' => $person->id])
            ->assertCreated();

        $person->delete();

        $this->getJson("/api/assignments/{$vehicle->id}")
            ->assertOk()
            ->assertJsonPath('data.person.id', $person->id)
            ->assertJsonPath('data.person.deleted_at', fn (?string $value) => $value !== null);
    }

    #[Test]
    public function rechaza_una_fecha_de_devolucion_en_el_pasado(): void
    {
        $vehicle = Vehicle::factory()->create();
        $site = $this->createSite();
        $person = Person::factory()->create();

        $this->putJson("/api/assignments/{$vehicle->id}", [
            'site_id' => $site->id,
            'person_id' => $person->id,
            'expected_return_at' => now()->subDay()->toDateString(),
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('expected_return_at');
    }

    #[Test]
    public function marca_como_atrasada_una_asignacion_vencida(): void
    {
        $vehicle = Vehicle::factory()->create();
        $site = $this->createSite();
        $person = Person::factory()->create();

        $assignment = VehicleAssignment::query()->create([
            'vehicle_id' => $vehicle->id,
            'site_id' => $site->id,
            'person_id' => $person->id,
            'assigned_at' => Carbon::now()->subDays(10),
            'expected_return_at' => Carbon::now()->subDays(3)->toDateString(),
        ]);

        $this->getJson("/api/assignments/{$vehicle->id}")
            ->assertOk()
            ->assertJsonPath('data.is_overdue', true);

        $this->assertTrue($assignment->refresh()->isOverdue());
    }

    #[Test]
    public function busca_personas_activas_por_nombre_o_documento(): void
    {
        Person::factory()->create(['first_name' => 'Juana', 'last_name' => 'Pérez']);
        Person::factory()->create(['first_name' => 'Carlos', 'last_name' => 'Soto']);
        Person::factory()->inactive()->create(['first_name' => 'Juanito', 'last_name' => 'Inactivo']);

        $this->getJson('/api/assignments/people?search=juana')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.full_name', 'Juana Pérez');
    }

    #[Test]
    public function lista_las_sedes_disponibles(): void
    {
        $this->createSite('main', 'Sede Central');
        $this->createSite('north', 'Sede Norte');

        $this->getJson('/api/assignments/sites')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function el_historial_de_un_vehiculo_devuelve_todas_sus_asignaciones(): void
    {
        $vehicle = Vehicle::factory()->create();
        $siteA = $this->createSite('main', 'Sede Central');
        $siteB = $this->createSite('north', 'Sede Norte');

        $this->putJson("/api/assignments/{$vehicle->id}", ['site_id' => $siteA->id])->assertCreated();
        $this->putJson("/api/assignments/{$vehicle->id}", ['site_id' => $siteB->id])->assertOk();

        $this->getJson("/api/assignments/{$vehicle->id}/history")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.site.id', $siteB->id)
            ->assertJsonPath('data.1.site.id', $siteA->id);
    }

    #[Test]
    public function lista_las_asignaciones_actuales_de_todos_los_vehiculos_y_permite_filtrar_por_sede(): void
    {
        $vehicleA = Vehicle::factory()->create();
        $vehicleB = Vehicle::factory()->create();
        $siteA = $this->createSite('main', 'Sede Central');
        $siteB = $this->createSite('north', 'Sede Norte');

        $this->putJson("/api/assignments/{$vehicleA->id}", ['site_id' => $siteA->id])->assertCreated();
        $this->putJson("/api/assignments/{$vehicleB->id}", ['site_id' => $siteB->id])->assertCreated();

        $this->getJson('/api/assignments')->assertOk()->assertJsonCount(2, 'data');

        $this->getJson("/api/assignments?site_id={$siteA->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.vehicle_id', $vehicleA->id);
    }
}
```

- [ ] **Step 2: Ejecutar y verificar que falla**

Run: `docker compose exec app php artisan test --filter=AssignmentApiTest`
Expected: FAIL — `site_id` no se valida, la respuesta no trae `site`, no existen `/sites`, `/history` ni el listado raíz.

- [ ] **Step 3: Actualizar el Request de validación**

`backend/app/Modules/Assignments/Http/Requests/AssignVehicleRequest.php` (reemplaza el archivo completo):

```php
<?php

declare(strict_types=1);

namespace App\Modules\Assignments\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'site_id' => [
                'required',
                'integer',
                Rule::exists('sites', 'id'),
            ],
            'person_id' => [
                'nullable',
                'integer',
                Rule::exists('people', 'id')->whereNull('deleted_at'),
            ],
            'expected_return_at' => ['nullable', 'date', 'after_or_equal:today'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'site_id' => 'sede',
            'person_id' => 'persona',
            'expected_return_at' => 'devolución prevista',
            'notes' => 'observaciones',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'person_id.exists' => 'La persona seleccionada no existe o fue eliminada.',
        ];
    }
}
```

- [ ] **Step 4: Actualizar el Resource, el Controller y las rutas**

`backend/app/Modules/Assignments/Http/Resources/VehicleAssignmentResource.php` (reemplaza el archivo completo):

```php
<?php

declare(strict_types=1);

namespace App\Modules\Assignments\Http\Resources;

use App\Modules\Assignments\Models\VehicleAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Contrato JSON del módulo de Assignments. Cualquier cambio aquí debe
 * reflejarse en la interfaz VehicleAssignment del frontend.
 *
 * @mixin VehicleAssignment
 */
class VehicleAssignmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_id' => $this->vehicle_id,
            'site' => $this->site !== null ? [
                'id' => $this->site->id,
                'code' => $this->site->code,
                'name' => $this->site->name,
            ] : null,
            'person' => $this->person !== null ? [
                'id' => $this->person->id,
                'full_name' => $this->person->full_name,
                'document_number' => $this->person->document_number,
                'site' => $this->person->site,
                'is_active' => $this->person->is_active,
                'deleted_at' => $this->person->deleted_at?->toIso8601String(),
            ] : null,
            'notes' => $this->notes,
            'assigned_at' => $this->assigned_at->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'expected_return_at' => $this->expected_return_at?->toDateString(),
            'is_overdue' => $this->isOverdue(),
        ];
    }
}
```

`backend/app/Modules/Assignments/Http/Controllers/AssignmentController.php` (reemplaza el archivo completo):

```php
<?php

declare(strict_types=1);

namespace App\Modules\Assignments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Assignments\Http\Requests\AssignVehicleRequest;
use App\Modules\Assignments\Http\Resources\VehicleAssignmentResource;
use App\Modules\Assignments\Models\Site;
use App\Modules\Assignments\Services\AssignmentService;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AssignmentController extends Controller
{
    public function __construct(private readonly AssignmentService $assignments) {}

    /**
     * GET /api/assignments?site_id=
     *
     * Asignaciones actuales de todos los vehículos (una por vehículo, la
     * vigente), opcionalmente filtradas por sede.
     */
    public function index(Request $request): JsonResponse
    {
        $siteId = $request->integer('site_id') ?: null;

        return response()->json([
            'data' => VehicleAssignmentResource::collection($this->assignments->currentAll($siteId)),
        ]);
    }

    /**
     * GET /api/assignments/sites
     */
    public function sites(): JsonResponse
    {
        return response()->json([
            'data' => $this->assignments->sites()->map(fn (Site $site): array => [
                'id' => $site->id,
                'code' => $site->code,
                'name' => $site->name,
            ]),
        ]);
    }

    /**
     * GET /api/assignments/people?search=
     *
     * Búsqueda liviana de personas activas para el selector del frontend.
     */
    public function people(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->assignments->searchPeople($request->string('search')->toString() ?: null),
        ]);
    }

    /**
     * GET /api/assignments/{vehicle}
     */
    public function show(Vehicle $vehicle): JsonResponse
    {
        $assignment = $this->assignments->current($vehicle);

        return response()->json([
            'data' => $assignment ? VehicleAssignmentResource::make($assignment) : null,
        ]);
    }

    /**
     * GET /api/assignments/{vehicle}/history
     */
    public function history(Vehicle $vehicle): JsonResponse
    {
        return response()->json([
            'data' => VehicleAssignmentResource::collection($this->assignments->history($vehicle)),
        ]);
    }

    /**
     * PUT|PATCH /api/assignments/{vehicle}
     *
     * Traslada/(re)asigna el vehículo: cierra la asignación activa (si la
     * hay) y crea una nueva.
     */
    public function store(AssignVehicleRequest $request, Vehicle $vehicle): VehicleAssignmentResource
    {
        $personId = $request->validated('person_id');

        $assignment = $this->assignments->assign(
            $vehicle,
            (int) $request->validated('site_id'),
            $personId !== null ? (int) $personId : null,
            $request->validated('expected_return_at'),
            $request->validated('notes'),
        );

        return VehicleAssignmentResource::make($assignment);
    }

    /**
     * DELETE /api/assignments/{vehicle}
     *
     * Cierra la asignación activa (no borra el historial).
     */
    public function destroy(Vehicle $vehicle): Response
    {
        $this->assignments->unassign($vehicle);

        return response()->noContent();
    }
}
```

`backend/app/Modules/Assignments/Routes/api.php` (reemplaza el archivo completo):

```php
<?php

declare(strict_types=1);

use App\Modules\Assignments\Http\Controllers\AssignmentController;
use Illuminate\Support\Facades\Route;

/*
|---------------------------------------------------------------------------
| Rutas del módulo de Assignments
|---------------------------------------------------------------------------
|
| Se publican automáticamente bajo el prefijo /api/assignments con el grupo
| de middleware "api" (ver App\Support\Module\ModuleServiceProvider).
|
| Este módulo es el único puente entre Vehículos y Personas.
|
*/

Route::get('/', [AssignmentController::class, 'index'])->name('index');
Route::get('sites', [AssignmentController::class, 'sites'])->name('sites');
Route::get('people', [AssignmentController::class, 'people'])->name('people');

Route::get('{vehicle}/history', [AssignmentController::class, 'history'])->name('history');
Route::get('{vehicle}', [AssignmentController::class, 'show'])->name('show');
Route::match(['put', 'patch'], '{vehicle}', [AssignmentController::class, 'store'])->name('store');
Route::delete('{vehicle}', [AssignmentController::class, 'destroy'])->name('destroy');
```

- [ ] **Step 5: Ejecutar y verificar que pasa**

Run: `docker compose exec app php artisan test --filter=AssignmentApiTest`
Expected: PASS (17 tests).

- [ ] **Step 6: Ejecutar toda la suite del backend para detectar regresiones**

Run: `docker compose exec app php artisan test`
Expected: PASS (todo el backend, incluyendo Vehicles y Persons, que no deberían haberse visto afectados).

- [ ] **Step 7: Actualizar el README del módulo**

`backend/app/Modules/Assignments/README.md` (reemplaza el archivo completo):

```markdown
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
```

- [ ] **Step 8: Commit**

```bash
git add backend/app/Modules/Assignments/Http/Requests/AssignVehicleRequest.php \
        backend/app/Modules/Assignments/Http/Resources/VehicleAssignmentResource.php \
        backend/app/Modules/Assignments/Http/Controllers/AssignmentController.php \
        backend/app/Modules/Assignments/Routes/api.php \
        backend/tests/Feature/Assignments/AssignmentApiTest.php \
        backend/app/Modules/Assignments/README.md
git commit -m "feat(assignments): expone trazabilidad de vehículos por API (sede, historial, listado filtrable)"
```

---

## Task 6: Frontend — modelos, servicio y detalle del vehículo (sede + historial)

**Files:**
- Modify: `frontend/src/app/features/assignments/models/assignment.model.ts`
- Modify: `frontend/src/app/features/assignments/services/assignment.service.ts`
- Modify: `frontend/src/app/features/vehicles/pages/vehicle-detail/vehicle-detail.page.ts`
- Modify: `frontend/src/app/features/vehicles/pages/vehicle-detail/vehicle-detail.page.html`
- Modify: `frontend/src/app/features/vehicles/pages/vehicle-detail/vehicle-detail.page.scss`

**Interfaces:**
- Consumes: contrato JSON del Task 5 (`VehicleAssignmentResource`, `/sites`, `/history`).
- Produces: `Site` y `VehicleAssignment` (con `id`, `site`, `ended_at`, `person` opcional) en `assignment.model.ts`; `AssignmentService.getSites()`, `.history()`, `.listCurrent(siteId?)` en `assignment.service.ts` — `listCurrent` la consume el Task 7.

- [ ] **Step 1: Actualizar los modelos**

`frontend/src/app/features/assignments/models/assignment.model.ts` (reemplaza el archivo completo):

```ts
/**
 * Espejo de `App\Modules\Assignments\Http\Resources\VehicleAssignmentResource`.
 * Si cambia el recurso en el backend, hay que actualizar esta interfaz.
 */
export interface VehicleAssignment {
  id: number;
  vehicle_id: number;
  site: Site | null;
  person: AssignedPerson | null;
  notes: string | null;
  assigned_at: string;
  ended_at: string | null;
  expected_return_at: string | null;
  is_overdue: boolean;
}

/** Sede de un vehículo. Espejo de `App\Modules\Assignments\Models\Site`. */
export interface Site {
  id: number;
  code: string;
  name: string;
}

export interface AssignedPerson {
  id: number;
  full_name: string;
  document_number: string;
  site: string | null;
  is_active: boolean;
  deleted_at: string | null;
}

/** Resultado de GET /api/assignments/people (selector de personas). */
export interface PersonOption {
  id: number;
  full_name: string;
  document_number: string;
  site: string | null;
}

/** Cuerpo que acepta PUT /api/assignments/{vehicle}. */
export interface AssignVehiclePayload {
  site_id: number;
  person_id: number | null;
  expected_return_at: string | null;
  notes: string | null;
}
```

- [ ] **Step 2: Actualizar el servicio**

`frontend/src/app/features/assignments/services/assignment.service.ts` (reemplaza el archivo completo):

```ts
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';

import { ApiService } from '../../../core/api/api.service';
import { AssignVehiclePayload, PersonOption, Site, VehicleAssignment } from '../models/assignment.model';

/**
 * Única puerta de entrada hacia /api/assignments.
 *
 * Es el único punto del frontend que conoce tanto a Vehículos como a
 * Personas: la página de detalle de un vehículo la usa para mostrar y
 * gestionar la sede y el responsable asignados, sin que `features/vehicles`
 * tenga que importar nada de `features/persons`.
 */
@Injectable({ providedIn: 'root' })
export class AssignmentService {
  private readonly api = inject(ApiService);
  private readonly resource = 'assignments';

  current(vehicleId: number | string): Observable<VehicleAssignment | null> {
    return this.api.get<VehicleAssignment | null>(`${this.resource}/${vehicleId}`);
  }

  history(vehicleId: number | string): Observable<VehicleAssignment[]> {
    return this.api.get<VehicleAssignment[]>(`${this.resource}/${vehicleId}/history`);
  }

  /** Asignación actual de todos los vehículos, opcionalmente filtrada por sede. */
  listCurrent(siteId?: number): Observable<VehicleAssignment[]> {
    return this.api.get<VehicleAssignment[]>(this.resource, siteId ? { site_id: siteId } : {});
  }

  getSites(): Observable<Site[]> {
    return this.api.get<Site[]>(`${this.resource}/sites`);
  }

  assign(vehicleId: number | string, payload: AssignVehiclePayload): Observable<VehicleAssignment> {
    return this.api.put<VehicleAssignment>(`${this.resource}/${vehicleId}`, payload);
  }

  unassign(vehicleId: number | string): Observable<void> {
    return this.api.delete(`${this.resource}/${vehicleId}`);
  }

  searchPeople(search: string): Observable<PersonOption[]> {
    return this.api.get<PersonOption[]>(`${this.resource}/people`, { search });
  }
}
```

- [ ] **Step 3: Actualizar la página de detalle (lógica)**

`frontend/src/app/features/vehicles/pages/vehicle-detail/vehicle-detail.page.ts` (reemplaza el archivo completo):

```ts
import { DatePipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, effect, inject, input, signal } from '@angular/core';
import { takeUntilDestroyed, toSignal } from '@angular/core/rxjs-interop';
import { Router, RouterLink } from '@angular/router';
import { Subject, of } from 'rxjs';
import { catchError, debounceTime, distinctUntilChanged, switchMap } from 'rxjs/operators';
import { ButtonModule } from 'primeng/button';
import { InputTextModule } from 'primeng/inputtext';
import { TagModule } from 'primeng/tag';
import { TextareaModule } from 'primeng/textarea';

import { NotificationService } from '../../../../core/notifications/notification.service';
import { ConfirmService } from '../../../../shared/components/confirm-dialog/confirm.service';
import { IconComponent } from '../../../../shared/components/icon/icon';
import { SpinnerComponent } from '../../../../shared/components/spinner/spinner';
import { AssignmentService } from '../../../assignments/services/assignment.service';
import { PersonOption, Site, VehicleAssignment } from '../../../assignments/models/assignment.model';
import { Vehicle } from '../../models/vehicle.model';
import { VehicleService } from '../../services/vehicle.service';

/**
 * Detalle de solo lectura de un vehículo. Desde aquí se gestiona también la
 * foto (subir, reemplazar, quitar): al ser una acción independiente con su
 * propio endpoint, no tiene sentido meterla en el formulario de alta/edición.
 */
@Component({
  selector: 'app-vehicle-detail',
  changeDetection: ChangeDetectionStrategy.OnPush,
  imports: [
    RouterLink,
    IconComponent,
    SpinnerComponent,
    DatePipe,
    ButtonModule,
    InputTextModule,
    TagModule,
    TextareaModule,
  ],
  templateUrl: './vehicle-detail.page.html',
  styleUrl: './vehicle-detail.page.scss',
})
export class VehicleDetailPage {
  readonly id = input.required<string>();

  private readonly vehicles = inject(VehicleService);
  private readonly assignments = inject(AssignmentService);
  private readonly confirm = inject(ConfirmService);
  private readonly notifications = inject(NotificationService);
  private readonly router = inject(Router);

  protected readonly vehicle = signal<Vehicle | null>(null);
  protected readonly loading = signal(true);
  protected readonly uploadingPhoto = signal(false);

  protected readonly assignment = signal<VehicleAssignment | null>(null);
  protected readonly assignmentLoading = signal(true);
  protected readonly assigning = signal(false);
  protected readonly showAssignForm = signal(false);
  protected readonly selectedSiteId = signal<number | null>(null);
  protected readonly personQuery = signal('');
  protected readonly personResults = signal<PersonOption[]>([]);
  protected readonly searchingPeople = signal(false);
  protected readonly selectedPerson = signal<PersonOption | null>(null);
  protected readonly assignmentNotes = signal('');
  protected readonly expectedReturnAt = signal('');
  protected readonly todayIso = new Date().toISOString().slice(0, 10);

  protected readonly history = signal<VehicleAssignment[]>([]);
  protected readonly historyLoading = signal(true);

  protected readonly sites = toSignal(
    this.assignments.getSites().pipe(catchError(() => of([] as Site[]))),
    { initialValue: [] as Site[] },
  );

  /** Texto del buscador de personas, con retardo para no lanzar una petición por tecla. */
  private readonly personSearch = new Subject<string>();

  constructor() {
    effect(() => {
      const id = this.id();

      this.showAssignForm.set(false);
      this.selectedSiteId.set(null);
      this.personQuery.set('');
      this.personResults.set([]);
      this.selectedPerson.set(null);
      this.assignmentNotes.set('');
      this.expectedReturnAt.set('');

      this.load(id);
      this.loadAssignment(id);
      this.loadHistory(id);
    });

    this.personSearch
      .pipe(
        debounceTime(300),
        distinctUntilChanged(),
        switchMap((term) => {
          if (term.trim().length < 2) {
            this.searchingPeople.set(false);
            return of([]);
          }

          this.searchingPeople.set(true);

          return this.assignments.searchPeople(term).pipe(catchError(() => of([])));
        }),
        takeUntilDestroyed(),
      )
      .subscribe((results) => {
        this.personResults.set(results);
        this.searchingPeople.set(false);
      });
  }

  protected onPhotoSelected(input: HTMLInputElement): void {
    const file = input.files?.[0];

    if (!file) {
      return;
    }

    this.uploadingPhoto.set(true);

    this.vehicles.uploadPhoto(this.id(), file).subscribe({
      next: (vehicle) => {
        this.vehicle.set(vehicle);
        this.uploadingPhoto.set(false);
        this.notifications.success('La foto se actualizó correctamente.');
        input.value = '';
      },
      error: () => {
        this.uploadingPhoto.set(false);
        input.value = '';
      },
    });
  }

  protected async removePhoto(): Promise<void> {
    const accepted = await this.confirm.ask({
      title: 'Quitar foto',
      message: '¿Seguro que quieres quitar la foto de este vehículo?',
      confirmLabel: 'Quitar',
      danger: true,
    });

    if (!accepted) {
      return;
    }

    this.vehicles.deletePhoto(this.id()).subscribe({
      next: (vehicle) => {
        this.vehicle.set(vehicle);
        this.notifications.success('La foto se quitó correctamente.');
      },
    });
  }

  protected async remove(vehicle: Vehicle): Promise<void> {
    const accepted = await this.confirm.ask({
      title: 'Eliminar vehículo',
      message: `¿Seguro que quieres eliminar el vehículo de placa ${vehicle.plate}? Podrás restaurarlo más adelante.`,
      confirmLabel: 'Eliminar',
      danger: true,
    });

    if (!accepted) {
      return;
    }

    this.vehicles.remove(vehicle.id).subscribe({
      next: () => {
        this.notifications.success(`El vehículo ${vehicle.plate} se eliminó correctamente.`);
        void this.router.navigate(['/vehiculos']);
      },
    });
  }

  protected restore(vehicle: Vehicle): void {
    this.vehicles.restore(vehicle.id).subscribe({
      next: (updated) => {
        this.vehicle.set(updated);
        this.notifications.success(`El vehículo ${vehicle.plate} se restauró correctamente.`);
      },
    });
  }

  protected onSiteChange(value: string): void {
    this.selectedSiteId.set(value ? Number(value) : null);
  }

  protected onPersonSearch(value: string): void {
    this.personQuery.set(value);
    this.selectedPerson.set(null);
    this.personSearch.next(value);
  }

  protected selectPerson(person: PersonOption): void {
    this.selectedPerson.set(person);
    this.personQuery.set(person.full_name);
    this.personResults.set([]);
  }

  protected assign(): void {
    const siteId = this.selectedSiteId();

    if (!siteId) {
      return;
    }

    this.assigning.set(true);

    this.assignments
      .assign(this.id(), {
        site_id: siteId,
        person_id: this.selectedPerson()?.id ?? null,
        expected_return_at: this.expectedReturnAt() || null,
        notes: this.assignmentNotes().trim() || null,
      })
      .subscribe({
        next: (assignment) => {
          this.assignment.set(assignment);
          this.assigning.set(false);
          this.showAssignForm.set(false);
          this.selectedSiteId.set(null);
          this.personQuery.set('');
          this.selectedPerson.set(null);
          this.assignmentNotes.set('');
          this.expectedReturnAt.set('');
          this.loadHistory(this.id());
          this.notifications.success(
            assignment.person
              ? `Vehículo trasladado a ${assignment.site?.name} y asignado a ${assignment.person.full_name}.`
              : `Vehículo trasladado a ${assignment.site?.name}.`,
          );
        },
        error: () => this.assigning.set(false),
      });
  }

  protected async unassign(): Promise<void> {
    const accepted = await this.confirm.ask({
      title: 'Quitar asignación',
      message: '¿Seguro que quieres quitar la asignación de este vehículo?',
      confirmLabel: 'Quitar',
      danger: true,
    });

    if (!accepted) {
      return;
    }

    this.assignments.unassign(this.id()).subscribe({
      next: () => {
        this.assignment.set(null);
        this.loadHistory(this.id());
        this.notifications.success('Se quitó la asignación del vehículo.');
      },
    });
  }

  /** Texto breve de cuánto tiempo lleva asignado, a partir de la fecha ISO. */
  protected assignmentDuration(assignedAt: string): string {
    const days = Math.max(0, Math.floor((Date.now() - new Date(assignedAt).getTime()) / 86_400_000));

    if (days === 0) {
      return 'hoy';
    }

    if (days === 1) {
      return 'hace 1 día';
    }

    if (days < 30) {
      return `hace ${days} días`;
    }

    const months = Math.round(days / 30);

    return months === 1 ? 'hace 1 mes' : `hace ${months} meses`;
  }

  private load(id: string): void {
    this.loading.set(true);

    this.vehicles.find(id).subscribe({
      next: (vehicle) => {
        this.vehicle.set(vehicle);
        this.loading.set(false);
      },
      error: () => {
        this.loading.set(false);
        void this.router.navigate(['/vehiculos']);
      },
    });
  }

  private loadAssignment(id: string): void {
    this.assignmentLoading.set(true);

    this.assignments.current(id).subscribe({
      next: (assignment) => {
        this.assignment.set(assignment);
        this.assignmentLoading.set(false);
      },
      error: () => this.assignmentLoading.set(false),
    });
  }

  private loadHistory(id: string): void {
    this.historyLoading.set(true);

    this.assignments.history(id).subscribe({
      next: (history) => {
        this.history.set(history);
        this.historyLoading.set(false);
      },
      error: () => this.historyLoading.set(false),
    });
  }
}
```

- [ ] **Step 4: Actualizar la plantilla**

`frontend/src/app/features/vehicles/pages/vehicle-detail/vehicle-detail.page.html`: mantén tal cual las secciones `page-header`, `detail-photo` y `detail-info` (líneas 1–147 del archivo actual); reemplaza desde `<section class="card detail-assignment">` hasta el final del archivo por:

```html
  <section class="card detail-assignment">
    <h2 class="detail-assignment__title">Sede y responsable asignado</h2>

    @if (assignmentLoading()) {
      <app-spinner label="Cargando asignación…" />
    } @else {
      @if (assignment(); as current) {
        <div class="detail-assignment__current">
          <div class="detail-assignment__who">
            <div>
              <span class="cell-title">
                @if (current.person) {
                  {{ current.person.full_name }}
                } @else {
                  Sin responsable asignado
                }
              </span>
              <span class="cell-sub">
                {{ current.site?.name ?? 'Sede no registrada' }}
                @if (current.person) {
                  · {{ current.person.document_number }}
                  @if (current.person.deleted_at) {
                    · (persona eliminada)
                  }
                }
              </span>
            </div>

            @if (current.is_overdue) {
              <p-tag severity="danger" value="Atrasado" />
            }
          </div>

          <p class="detail-assignment__meta">
            @if (current.person) {
              Se lo llevó el {{ current.assigned_at | date: 'mediumDate' }} ({{
                assignmentDuration(current.assigned_at)
              }})
            } @else {
              En esta sede desde el {{ current.assigned_at | date: 'mediumDate' }} ({{
                assignmentDuration(current.assigned_at)
              }})
            }
            @if (current.expected_return_at) {
              · devolución prevista el {{ current.expected_return_at | date: 'mediumDate' }}
            }
          </p>

          @if (current.notes) {
            <p class="detail-assignment__notes">{{ current.notes }}</p>
          }

          <div class="detail-assignment__actions">
            @if (!showAssignForm()) {
              <p-button
                severity="secondary"
                [text]="true"
                size="small"
                icon="pi pi-pencil"
                label="Trasladar / reasignar"
                (onClick)="showAssignForm.set(true)"
              />
              <p-button
                severity="danger"
                [text]="true"
                size="small"
                icon="pi pi-trash"
                label="Quitar asignación"
                (onClick)="unassign()"
              />
            }
          </div>
        </div>
      }

      @if (!assignment() || showAssignForm()) {
        <div class="detail-assignment__form">
          <div class="field">
            <label class="field__label" for="assignment-site">Sede</label>
            <select
              id="assignment-site"
              class="input"
              [value]="selectedSiteId() ?? ''"
              (change)="onSiteChange($any($event.target).value)"
            >
              <option value="" disabled selected hidden>Selecciona una sede</option>
              @for (site of sites(); track site.id) {
                <option [value]="site.id">{{ site.name }}</option>
              }
            </select>
            <span class="field__hint">Obligatoria: a dónde queda el vehículo.</span>
          </div>

          <div class="field">
            <label class="field__label" for="assignment-person">Buscar persona (opcional)</label>
            <input
              id="assignment-person"
              class="input"
              pInputText
              type="search"
              placeholder="Nombre o documento…"
              [value]="personQuery()"
              (input)="onPersonSearch($any($event.target).value)"
            />

            @if (searchingPeople()) {
              <span class="field__hint">Buscando…</span>
            }

            @if (personResults().length > 0) {
              <ul class="detail-assignment__results">
                @for (person of personResults(); track person.id) {
                  <li>
                    <button type="button" class="detail-assignment__result" (click)="selectPerson(person)">
                      <span class="cell-title">{{ person.full_name }}</span>
                      <span class="cell-sub"
                        >{{ person.document_number }}{{ person.site ? ' · ' + person.site : '' }}</span
                      >
                    </button>
                  </li>
                }
              </ul>
            }
          </div>

          <div class="field">
            <label class="field__label" for="assignment-return">Devolución prevista</label>
            <input
              id="assignment-return"
              class="input"
              pInputText
              type="date"
              [min]="todayIso"
              [value]="expectedReturnAt()"
              (input)="expectedReturnAt.set($any($event.target).value)"
            />
            <span class="field__hint">Opcional: hasta cuándo se lleva el vehículo.</span>
          </div>

          <div class="field">
            <label class="field__label" for="assignment-notes">Observaciones</label>
            <textarea
              id="assignment-notes"
              class="textarea"
              pTextarea
              [value]="assignmentNotes()"
              (input)="assignmentNotes.set($any($event.target).value)"
            ></textarea>
          </div>

          <div class="detail-assignment__form-actions">
            @if (assignment()) {
              <p-button
                severity="secondary"
                [outlined]="true"
                size="small"
                label="Cancelar"
                (onClick)="showAssignForm.set(false)"
              />
            }
            <p-button
              severity="primary"
              size="small"
              [disabled]="!selectedSiteId() || assigning()"
              [loading]="assigning()"
              [label]="assigning() ? 'Guardando…' : assignment() ? 'Trasladar' : 'Asignar'"
              (onClick)="assign()"
            />
          </div>
        </div>
      }
    }
  </section>

  <section class="card detail-history">
    <h2 class="detail-assignment__title">Historial de sede y responsable</h2>

    @if (historyLoading()) {
      <app-spinner label="Cargando historial…" />
    } @else if (history().length === 0) {
      <p class="detail-history__empty">Este vehículo todavía no tiene movimientos registrados.</p>
    } @else {
      <table class="detail-history__table">
        <thead>
          <tr>
            <th>Sede</th>
            <th>Responsable</th>
            <th>Desde</th>
            <th>Hasta</th>
          </tr>
        </thead>
        <tbody>
          @for (row of history(); track row.id) {
            <tr>
              <td>{{ row.site?.name ?? 'Sin registrar' }}</td>
              <td>{{ row.person?.full_name ?? 'Sin responsable' }}</td>
              <td>{{ row.assigned_at | date: 'mediumDate' }}</td>
              <td>{{ row.ended_at ? (row.ended_at | date: 'mediumDate') : 'Actual' }}</td>
            </tr>
          }
        </tbody>
      </table>
    }
  </section>
}
```

- [ ] **Step 5: Añadir estilos para el historial**

En `frontend/src/app/features/vehicles/pages/vehicle-detail/vehicle-detail.page.scss`, añade al final del archivo:

```scss
.detail-history {
  margin-top: var(--sp-5);
  padding: var(--sp-5);

  &__empty {
    margin: 0;
    color: var(--c-text-muted);
  }

  &__table {
    width: 100%;
    border-collapse: collapse;

    th,
    td {
      padding: var(--sp-2) var(--sp-3);
      text-align: left;
      border-bottom: 1px solid var(--c-border);
    }

    th {
      font-size: 0.78rem;
      font-weight: 650;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      color: var(--c-text-subtle);
    }

    tr:last-child td {
      border-bottom: none;
    }
  }
}
```

- [ ] **Step 6: Compilar y verificar manualmente en el navegador**

Run:
```bash
docker compose up -d
docker compose exec app php artisan test --filter=Assignment
```

En el navegador (`http://localhost:4200`), sobre un vehículo existente:
- Verifica que "Asignar" queda deshabilitado hasta elegir una sede.
- Asigna una sede sin responsable → debe guardar y mostrar "Sin responsable asignado".
- Reasigna con responsable y otra sede → la tarjeta debe actualizarse y la sección "Historial" debe mostrar 2 filas (la anterior con fecha "Hasta", la nueva con "Actual").
- Quita la asignación → la tarjeta vuelve al formulario vacío y el historial sigue mostrando ambas filas.

Expected: sin errores en consola del navegador; el flujo completo funciona.

- [ ] **Step 7: Commit**

```bash
git add frontend/src/app/features/assignments/models/assignment.model.ts \
        frontend/src/app/features/assignments/services/assignment.service.ts \
        frontend/src/app/features/vehicles/pages/vehicle-detail/vehicle-detail.page.ts \
        frontend/src/app/features/vehicles/pages/vehicle-detail/vehicle-detail.page.html \
        frontend/src/app/features/vehicles/pages/vehicle-detail/vehicle-detail.page.scss
git commit -m "feat(vehicles): detalle del vehículo gestiona sede + responsable opcional y muestra historial"
```

---

## Task 7: Frontend — listado de vehículos con sede/responsable y filtro por sede

**Files:**
- Modify: `frontend/src/app/features/vehicles/pages/vehicle-list/vehicle-list.page.ts`
- Modify: `frontend/src/app/features/vehicles/pages/vehicle-list/vehicle-list.page.html`

**Interfaces:**
- Consumes: `AssignmentService.listCurrent(siteId?)` y `.getSites()` (Task 6).

> **Nota de diseño:** el backend de Vehículos no conoce Assignments (por diseño: son módulos independientes, ver spec). El filtro por sede se resuelve en el cliente, comparando los vehículos de la página actual contra las asignaciones vigentes de esa sede — no es un filtro de servidor sobre `/api/vehicles`. Para la flota de este proyecto (~30 vehículos, `per_page` 15) es una limitación aceptable; si la flota creciera mucho, una mejora futura sería paginar directamente desde `/api/assignments`.

- [ ] **Step 1: Actualizar la lógica de la página**

En `frontend/src/app/features/vehicles/pages/vehicle-list/vehicle-list.page.ts`, añade los imports:

```ts
import { AssignmentService } from '../../../assignments/services/assignment.service';
import { Site, VehicleAssignment } from '../../../assignments/models/assignment.model';
```

Dentro de la clase `VehicleListPage`, junto a `private readonly vehicles = inject(VehicleService);`, añade:

```ts
  private readonly assignments = inject(AssignmentService);

  protected readonly siteFilter = signal<number | null>(null);

  protected readonly sites = toSignal(
    this.assignments.getSites().pipe(catchError(() => of([] as Site[]))),
    { initialValue: [] as Site[] },
  );

  protected readonly siteOptions = computed(() => [
    { value: null, label: 'Todas' },
    ...this.sites().map((site) => ({ value: site.id, label: site.name })),
  ]);

  /** Asignaciones vigentes; con filtro de sede activo, ya vienen acotadas a esa sede. */
  private readonly currentAssignments = toSignal(
    toObservable(this.siteFilter).pipe(
      switchMap((siteId) =>
        this.assignments.listCurrent(siteId ?? undefined).pipe(catchError(() => of([] as VehicleAssignment[]))),
      ),
    ),
    { initialValue: [] as VehicleAssignment[] },
  );

  /** vehicle_id -> asignación vigente, para pintar sede/responsable en cada fila. */
  protected readonly assignmentByVehicle = computed(() => {
    const map = new Map<number, VehicleAssignment>();

    for (const assignment of this.currentAssignments()) {
      map.set(assignment.vehicle_id, assignment);
    }

    return map;
  });

  /**
   * Filtrado en cliente sobre la página actual: ver nota de diseño al inicio
   * del Task 7 del plan de trazabilidad de vehículos.
   */
  protected readonly visibleItems = computed(() => {
    const siteId = this.siteFilter();

    if (siteId === null) {
      return this.items();
    }

    const assignedVehicleIds = new Set(this.currentAssignments().map((a) => a.vehicle_id));

    return this.items().filter((vehicle) => assignedVehicleIds.has(vehicle.id));
  });

  protected onSiteFilterChange(siteId: number | null): void {
    this.siteFilter.set(siteId);
  }
```

Actualiza `hasFilters` para incluir el filtro de sede:

```ts
  protected readonly hasFilters = computed(() => {
    const f = this.filters();
    return (
      f.search !== '' ||
      f.type !== '' ||
      f.fuel_type !== '' ||
      f.brand !== '' ||
      f.year_from !== '' ||
      f.year_to !== '' ||
      f.is_active !== '' ||
      f.with_trashed ||
      this.siteFilter() !== null
    );
  });
```

Actualiza `resetFilters` para limpiar también la sede:

```ts
  protected resetFilters(): void {
    this.filters.set({ ...DEFAULT_VEHICLE_FILTERS });
    this.siteFilter.set(null);
  }
```

- [ ] **Step 2: Actualizar la plantilla**

En `frontend/src/app/features/vehicles/pages/vehicle-list/vehicle-list.page.html`, añade un nuevo filtro justo después del bloque `<div class="field">` de "Estado" (que termina en la línea con `(ngModelChange)="patchFilters({ is_active: $event })"`):

```html
    <div class="field">
      <label class="field__label" for="vehicle-site">Sede</label>
      <p-select
        inputId="vehicle-site"
        [options]="siteOptions()"
        optionLabel="label"
        optionValue="value"
        [ngModel]="siteFilter()"
        name="site_id"
        (ngModelChange)="onSiteFilterChange($event)"
      />
    </div>
```

Cambia `[value]="items()"` por `[value]="visibleItems()"` en el `<p-table>`.

Añade una columna "Sede / Responsable" en el `<ng-template #header>`, justo antes de `<th class="cell-actions">Acciones</th>`:

```html
            <th>Sede / Responsable</th>
```

Añade la celda correspondiente en el `<ng-template #body let-vehicle>`, justo antes de `<td class="cell-actions">`:

```html
            <td>
              @if (assignmentByVehicle().get(vehicle.id); as current) {
                <span class="cell-title">{{ current.site?.name ?? 'Sin registrar' }}</span>
                <span class="cell-sub">{{ current.person?.full_name ?? 'Sin responsable' }}</span>
              } @else {
                <span class="cell-sub">Sin asignación</span>
              }
            </td>
```

- [ ] **Step 3: Compilar y verificar manualmente en el navegador**

Run: `docker compose up -d` (si no está corriendo).

En `http://localhost:4200/vehiculos`:
- Verifica que aparece la columna "Sede / Responsable" con los datos correctos para los vehículos que tienen asignación (usa el flujo del Task 6 para crear alguna).
- Selecciona una sede en el nuevo filtro y confirma que solo quedan visibles los vehículos de la página actual con asignación vigente en esa sede.
- "Limpiar filtros" debe restablecer también el filtro de sede.

Expected: sin errores en consola del navegador; la tabla se actualiza correctamente.

- [ ] **Step 4: Commit**

```bash
git add frontend/src/app/features/vehicles/pages/vehicle-list/vehicle-list.page.ts \
        frontend/src/app/features/vehicles/pages/vehicle-list/vehicle-list.page.html
git commit -m "feat(vehicles): listado muestra sede/responsable actual y permite filtrar por sede"
```
