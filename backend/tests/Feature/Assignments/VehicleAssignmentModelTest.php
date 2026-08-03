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
