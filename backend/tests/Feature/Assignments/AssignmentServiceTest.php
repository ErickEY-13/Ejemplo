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
