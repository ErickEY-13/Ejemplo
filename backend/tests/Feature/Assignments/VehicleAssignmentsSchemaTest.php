<?php

declare(strict_types=1);

namespace Tests\Feature\Assignments;

use App\Modules\Assignments\Models\Site;
use App\Modules\Persons\Models\Person;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VehicleAssignmentsSchemaTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION_PATH = 'app/Modules/Assignments/Database/Migrations/2026_01_01_000303_add_site_and_history_to_vehicle_assignments_table.php';

    /**
     * Carga la migración real desde disco, tal como hace el propio Migrator
     * de Laravel (que usa `require`, no una copia de su SQL). Cada llamada
     * devuelve una instancia nueva de la clase anónima definida en el
     * archivo, así que es seguro invocarla más de una vez por test.
     */
    private function loadRealMigration(): Migration
    {
        return require base_path(self::MIGRATION_PATH);
    }

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

    #[Test]
    public function down_migration_cleans_up_null_person_ids(): void
    {
        $vehicle = Vehicle::factory()->create();
        $site = Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);
        $person = Person::factory()->create();

        // Fila válida: tiene responsable, sobrevive tal cual al rollback.
        $validId = DB::table('vehicle_assignments')->insertGetId([
            'vehicle_id' => $vehicle->id,
            'site_id' => $site->id,
            'person_id' => $person->id,
            'assigned_at' => now()->subDay(),
            'ended_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Fila que solo el nuevo esquema permite: sin responsable asignado.
        DB::table('vehicle_assignments')->insert([
            'vehicle_id' => $vehicle->id,
            'site_id' => $site->id,
            'person_id' => null,
            'assigned_at' => now(),
            'ended_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(
            1,
            DB::table('vehicle_assignments')->where('vehicle_id', $vehicle->id)->whereNull('person_id')->count()
        );

        // Invoca el down() REAL de la migración, no una copia de su SQL.
        $this->loadRealMigration()->down();

        $this->assertSame(
            0,
            DB::table('vehicle_assignments')->where('vehicle_id', $vehicle->id)->whereNull('person_id')->count(),
            'down() debe eliminar las filas con person_id NULL antes de reimponer el NOT NULL.'
        );

        // La fila válida debe seguir intacta.
        $this->assertDatabaseHas('vehicle_assignments', [
            'id' => $validId,
            'vehicle_id' => $vehicle->id,
            'person_id' => $person->id,
        ]);

        // Confirma que down() realmente reimpuso el NOT NULL en el esquema
        // real (no solo que la fila haya sido borrada por casualidad).
        $this->expectException(QueryException::class);
        DB::table('vehicle_assignments')->insert([
            'vehicle_id' => $vehicle->id,
            'person_id' => null,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    #[Test]
    public function down_migration_deduplicates_vehicle_ids_with_non_null_persons(): void
    {
        $vehicle = Vehicle::factory()->create();
        $site = Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);

        $person1 = Person::factory()->create();
        $person2 = Person::factory()->create();

        // Primera asignación (más antigua).
        DB::table('vehicle_assignments')->insertGetId([
            'vehicle_id' => $vehicle->id,
            'site_id' => $site->id,
            'person_id' => $person1->id,
            'assigned_at' => now()->subDay(),
            'ended_at' => now()->subHours(2),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Segunda asignación para el mismo vehículo (más reciente).
        $secondAssignmentId = DB::table('vehicle_assignments')->insertGetId([
            'vehicle_id' => $vehicle->id,
            'site_id' => $site->id,
            'person_id' => $person2->id,
            'assigned_at' => now(),
            'ended_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(2, DB::table('vehicle_assignments')->where('vehicle_id', $vehicle->id)->count());

        // Invoca el down() REAL de la migración, no una copia de su SQL.
        $this->loadRealMigration()->down();

        $remaining = DB::table('vehicle_assignments')->where('vehicle_id', $vehicle->id)->get();

        $this->assertCount(
            1,
            $remaining,
            'down() debe dejar una sola fila por vehicle_id antes de reimponer el índice único.'
        );
        $this->assertSame($secondAssignmentId, $remaining->first()->id, 'Debe sobrevivir la asignación más reciente.');
        $this->assertEquals($person2->id, $remaining->first()->person_id);

        // Confirma que down() realmente reimpuso el índice único en el
        // esquema real (no solo que las filas duplicadas se hayan borrado).
        $this->expectException(QueryException::class);
        DB::table('vehicle_assignments')->insert([
            'vehicle_id' => $vehicle->id,
            'person_id' => $person1->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    #[Test]
    public function down_migration_leaves_schema_usable_by_up_again(): void
    {
        // Guarda contra la contaminación de esquema entre tests: si down()
        // dejara el esquema en un estado intermedio, volver a correr up()
        // fallaría (columnas duplicadas, constraint ya reimpuesto, etc.).
        $vehicle = Vehicle::factory()->create();
        $site = Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);
        $person = Person::factory()->create();

        // Fila con responsable: sobrevive a la limpieza de down() (que solo
        // borra las filas con person_id NULL), así podemos confirmar después
        // que el esquema post-up() sigue viendo esta fila intacta.
        DB::table('vehicle_assignments')->insertGetId([
            'vehicle_id' => $vehicle->id,
            'site_id' => $site->id,
            'person_id' => $person->id,
            'assigned_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->loadRealMigration()->down();
        $this->loadRealMigration()->up();

        // Tras volver a subir la migración, el esquema debe aceptar de
        // nuevo varias filas por vehículo y responsables nulos.
        $newSite = Site::query()->create(['code' => 'secondary', 'name' => 'Sede Norte']);

        DB::table('vehicle_assignments')->insert([
            'vehicle_id' => $vehicle->id,
            'site_id' => $newSite->id,
            'person_id' => null,
            'assigned_at' => now(),
            'ended_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(2, DB::table('vehicle_assignments')->where('vehicle_id', $vehicle->id)->count());
        $this->assertSame(
            1,
            DB::table('vehicle_assignments')->where('vehicle_id', $vehicle->id)->whereNull('person_id')->count()
        );
    }
}
