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
