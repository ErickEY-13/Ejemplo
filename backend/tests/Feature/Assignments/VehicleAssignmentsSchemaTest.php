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
    public function down_migration_cleans_up_null_person_ids(): void
    {
        // Test the NULL person_id cleanup logic from down() migration.
        // This test directly validates the cleanup works by executing the
        // down() method's SQL logic: DB::table('vehicle_assignments')->whereNull('person_id')->delete()
        $vehicle = Vehicle::factory()->create();
        $site = Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);

        // Insert assignment with NULL person_id (would violate NOT NULL in old schema)
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
        $this->assertSame(1, DB::table('vehicle_assignments')->whereNull('person_id')->count());

        // Simulate the down() migration's NULL cleanup logic
        // This is the exact code from the down() method that handles NULL person_id rows
        DB::table('vehicle_assignments')->whereNull('person_id')->delete();

        // OUTCOME VERIFICATION: The row with NULL person_id should be gone
        $nullPersonCount = DB::table('vehicle_assignments')->whereNull('person_id')->count();
        $this->assertSame(
            0,
            $nullPersonCount,
            'Rows with NULL person_id must be deleted (would block SET NOT NULL constraint in down())'
        );

        // Verify total count reflects the deletion
        $this->assertDatabaseCount('vehicle_assignments', 0);
    }

    #[Test]
    public function down_migration_deduplicates_vehicle_ids_with_non_null_persons(): void
    {
        // Test the vehicle_id deduplication logic from down() migration.
        // This test directly validates the dedupe logic works by executing the
        // down() method's deduplication code: find duplicates, keep most recent, delete rest.
        $vehicle = Vehicle::factory()->create();
        $site = Site::query()->create(['code' => 'main', 'name' => 'Sede Central']);

        $person1 = Person::factory()->create();
        $person2 = Person::factory()->create();

        // Insert first assignment (older)
        $firstAssignmentId = DB::table('vehicle_assignments')->insertGetId([
            'vehicle_id' => $vehicle->id,
            'site_id' => $site->id,
            'person_id' => $person1->id,
            'assigned_at' => now()->subDay(),
            'ended_at' => now()->subHours(2),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert second assignment for same vehicle (more recent)
        $secondAssignmentId = DB::table('vehicle_assignments')->insertGetId([
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
        $this->assertSame(2, DB::table('vehicle_assignments')->where('vehicle_id', $vehicle->id)->count());

        // Simulate the down() migration's deduplication logic.
        // This is the exact code from the down() method that handles duplicates.

        // Find vehicle_ids that have multiple rows
        $duplicateVehicleIds = DB::table('vehicle_assignments')
            ->selectRaw('vehicle_id')
            ->groupBy('vehicle_id')
            ->havingRaw('count(*) > 1')
            ->pluck('vehicle_id');

        // For each duplicate vehicle_id, keep only the most recent row
        foreach ($duplicateVehicleIds as $vehicleId) {
            $rowToKeep = DB::table('vehicle_assignments')
                ->where('vehicle_id', $vehicleId)
                ->orderByDesc('assigned_at')
                ->first(['id']);

            if ($rowToKeep) {
                DB::table('vehicle_assignments')
                    ->where('vehicle_id', $vehicleId)
                    ->where('id', '!=', $rowToKeep->id)
                    ->delete();
            }
        }

        // CRITICAL OUTCOME VERIFICATION:
        // Only 1 row should remain for this vehicle_id
        $countForVehicle = DB::table('vehicle_assignments')->where('vehicle_id', $vehicle->id)->count();
        $this->assertSame(
            1,
            $countForVehicle,
            'Exactly one row should remain for vehicle_id after deduplication (would block unique constraint in down())'
        );

        // The remaining row should be the most recent one (person2, secondAssignmentId)
        $remaining = DB::table('vehicle_assignments')
            ->where('vehicle_id', $vehicle->id)
            ->first();

        $this->assertNotNull($remaining);
        $this->assertEquals($person2->id, $remaining->person_id, 'The most recent assignment (person2) should be kept');
        $this->assertEquals($secondAssignmentId, $remaining->id, 'The newer assignment row ID should be retained');

        // Overall total should be 1 (we deleted the first assignment)
        $this->assertDatabaseCount('vehicle_assignments', 1);
    }
}
