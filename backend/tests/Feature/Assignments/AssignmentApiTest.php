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
