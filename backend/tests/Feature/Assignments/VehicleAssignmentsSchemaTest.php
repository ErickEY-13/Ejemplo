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

    #[Test]
    public function rollback_migration_completes_without_error(): void
    {
        // Setup: Create historical data that would violate old constraints after rollback
        $vehicle = Vehicle::factory()->create();
        $site = Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);

        // Insert first assignment with NULL person_id (would violate NOT NULL constraint in old schema)
        DB::table('vehicle_assignments')->insert([
            'vehicle_id' => $vehicle->id,
            'site_id' => $site->id,
            'person_id' => null,
            'assigned_at' => now()->subDay(),
            'ended_at' => now()->subHours(2),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert second assignment for same vehicle with valid person_id
        // (would violate unique constraint on vehicle_id in old schema)
        $person = \App\Modules\Persons\Models\Person::factory()->create();
        DB::table('vehicle_assignments')->insert([
            'vehicle_id' => $vehicle->id,
            'site_id' => $site->id,
            'person_id' => $person->id,
            'assigned_at' => now(),
            'ended_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Verify we have the new state: 2 rows for same vehicle, one with NULL person_id
        $this->assertDatabaseCount('vehicle_assignments', 2);

        // This is the critical test: rollback should complete without constraint violation errors
        // The down() migration should handle data cleanup safely
        $result = $this->artisan('migrate:rollback', ['--step' => 1]);

        // The key assertion: rollback must succeed (exit code 0)
        // If the down() method doesn't clean up constraint-violating data first,
        // Postgres will reject the SET NOT NULL and unique() operations
        $result->assertExitCode(0);
    }
}
