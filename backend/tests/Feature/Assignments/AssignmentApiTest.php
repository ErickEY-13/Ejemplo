<?php

declare(strict_types=1);

namespace Tests\Feature\Assignments;

use App\Modules\Persons\Models\Person;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AssignmentApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function un_vehiculo_sin_asignar_devuelve_null(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->getJson("/api/assignments/{$vehicle->id}")
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    #[Test]
    public function asigna_un_vehiculo_a_una_persona(): void
    {
        $vehicle = Vehicle::factory()->create();
        $person = Person::factory()->create(['site' => 'Obras Públicas']);

        $this->putJson("/api/assignments/{$vehicle->id}", [
            'person_id' => $person->id,
            'notes' => 'Uso exclusivo en horario laboral.',
        ])
            ->assertCreated()
            ->assertJsonPath('data.person.id', $person->id)
            ->assertJsonPath('data.person.full_name', $person->full_name)
            ->assertJsonPath('data.person.site', 'Obras Públicas')
            ->assertJsonPath('data.notes', 'Uso exclusivo en horario laboral.');

        $this->assertDatabaseHas('vehicle_assignments', [
            'vehicle_id' => $vehicle->id,
            'person_id' => $person->id,
        ]);

        $this->getJson("/api/assignments/{$vehicle->id}")
            ->assertOk()
            ->assertJsonPath('data.person.id', $person->id);
    }

    #[Test]
    public function reasignar_reemplaza_la_asignacion_anterior(): void
    {
        $vehicle = Vehicle::factory()->create();
        $primera = Person::factory()->create();
        $segunda = Person::factory()->create();

        $this->putJson("/api/assignments/{$vehicle->id}", ['person_id' => $primera->id])->assertCreated();
        $this->putJson("/api/assignments/{$vehicle->id}", ['person_id' => $segunda->id])
            ->assertOk()
            ->assertJsonPath('data.person.id', $segunda->id);

        $this->assertDatabaseCount('vehicle_assignments', 1);
        $this->assertDatabaseHas('vehicle_assignments', [
            'vehicle_id' => $vehicle->id,
            'person_id' => $segunda->id,
        ]);
    }

    #[Test]
    public function rechaza_una_persona_inexistente(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->putJson("/api/assignments/{$vehicle->id}", ['person_id' => 999999])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('person_id');
    }

    #[Test]
    public function rechaza_una_persona_eliminada(): void
    {
        $vehicle = Vehicle::factory()->create();
        $person = Person::factory()->create();
        $person->delete();

        $this->putJson("/api/assignments/{$vehicle->id}", ['person_id' => $person->id])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('person_id');
    }

    #[Test]
    public function quita_la_asignacion(): void
    {
        $vehicle = Vehicle::factory()->create();
        $person = Person::factory()->create();

        $this->putJson("/api/assignments/{$vehicle->id}", ['person_id' => $person->id])->assertCreated();

        $this->deleteJson("/api/assignments/{$vehicle->id}")->assertNoContent();

        $this->assertDatabaseMissing('vehicle_assignments', ['vehicle_id' => $vehicle->id]);

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
        $person = Person::factory()->create();

        $this->putJson("/api/assignments/{$vehicle->id}", ['person_id' => $person->id])->assertCreated();

        $person->delete();

        $this->getJson("/api/assignments/{$vehicle->id}")
            ->assertOk()
            ->assertJsonPath('data.person.id', $person->id)
            ->assertJsonPath('data.person.deleted_at', fn (?string $value) => $value !== null);
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
}
