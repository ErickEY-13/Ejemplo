<?php

declare(strict_types=1);

namespace Tests\Feature\Assignments;

use App\Modules\Assignments\Models\Site;
use App\Modules\Persons\Models\Person;
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

    #[Test]
    public function rollback_migration_cleans_up_null_person_ids(): void
    {
        // Test the NULL person_id cleanup path in down() migration
        $vehicle = Vehicle::factory()->create();
        $site = Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);

        // Insert assignment with NULL person_id
        DB::table('vehicle_assignments')->insert([
            'vehicle_id' => $vehicle->id,
            'site_id' => $site->id,
            'person_id' => null,
            'assigned_at' => now(),
            'ended_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseCount('vehicle_assignments', 1);

        // Rollback should complete without error
        $result = $this->artisan('migrate:rollback', ['--step' => 1]);
        $result->assertExitCode(0);
    }

    #[Test]
    public function rollback_migration_deduplicates_vehicle_ids_with_non_null_persons(): void
    {
        // Test the vehicle_id deduplication loop in down() migration.
        // This is the critical test: verifies that when multiple rows exist
        // for the same vehicle_id (all with non-null person_ids), the down()
        // method does NOT crash with a unique constraint violation.
        //
        // Without the deduplication logic in down(), this test would fail with:
        // "SQLSTATE[23505]: Unique violation: 7 ERROR: duplicate key value
        // violates unique constraint 'vehicle_assignments_vehicle_id_unique'"
        $vehicle = Vehicle::factory()->create();
        $site = Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);

        $person1 = Person::factory()->create();
        $person2 = Person::factory()->create();

        // Insert first assignment (older)
        DB::table('vehicle_assignments')->insert([
            'vehicle_id' => $vehicle->id,
            'site_id' => $site->id,
            'person_id' => $person1->id,
            'assigned_at' => now()->subDay(),
            'ended_at' => now()->subHours(2),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert second assignment for same vehicle (more recent)
        // Both rows have non-null person_id, so they pass through the NULL filter
        // and reach the duplicate-detection loop in down()
        DB::table('vehicle_assignments')->insert([
            'vehicle_id' => $vehicle->id,
            'site_id' => $site->id,
            'person_id' => $person2->id,
            'assigned_at' => now(),
            'ended_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify we have 2 rows for the same vehicle_id with valid person_ids
        $this->assertDatabaseCount('vehicle_assignments', 2);

        // Rollback the migration. This is the CRITICAL TEST.
        // Without the down() deduplication logic, Postgres would reject the
        // unique constraint re-add with:
        //   ERROR: could not create unique index "vehicle_assignments_vehicle_id_unique"
        //   DETAIL: Key (vehicle_id)=(X) is duplicated.
        // The fact that this completes with exit code 0 proves the down() method
        // properly handles duplicate vehicle_ids before re-adding the constraint.
        $result = $this->artisan('migrate:rollback', ['--step' => 1]);
        $result->assertExitCode(0);
    }
}
