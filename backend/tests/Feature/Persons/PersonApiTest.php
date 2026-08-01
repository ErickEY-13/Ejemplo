<?php

declare(strict_types=1);

namespace Tests\Feature\Persons;

use App\Modules\Persons\Enums\DocumentType;
use App\Modules\Persons\Enums\Gender;
use App\Modules\Persons\Models\Person;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PersonApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function lista_personas_paginadas(): void
    {
        Person::factory()->count(3)->create();

        $this->getJson('/api/persons')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'full_name', 'document_number', 'is_active']],
                'meta' => ['current_page', 'total', 'per_page'],
            ]);
    }

    #[Test]
    public function filtra_por_texto_de_busqueda(): void
    {
        Person::factory()->create(['first_name' => 'Amalia', 'last_name' => 'Quispe']);
        Person::factory()->create(['first_name' => 'Bruno', 'last_name' => 'Salas']);

        $this->getJson('/api/persons?search=amalia')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.first_name', 'Amalia');
    }

    #[Test]
    public function crea_una_persona(): void
    {
        $payload = [
            'document_type' => DocumentType::NationalId->value,
            'document_number' => '12345678',
            'first_name' => 'Lucía',
            'last_name' => 'Ramos',
            'gender' => Gender::Female->value,
            'email' => 'lucia@example.com',
        ];

        $this->postJson('/api/persons', $payload)
            ->assertCreated()
            ->assertJsonPath('data.full_name', 'Lucía Ramos')
            ->assertJsonPath('data.document_number', '12345678');

        $this->assertDatabaseHas('people', ['document_number' => '12345678']);
    }

    #[Test]
    public function rechaza_documentos_duplicados_del_mismo_tipo(): void
    {
        Person::factory()->create([
            'document_type' => DocumentType::NationalId,
            'document_number' => '12345678',
        ]);

        $this->postJson('/api/persons', [
            'document_type' => DocumentType::NationalId->value,
            'document_number' => '12345678',
            'first_name' => 'Otro',
            'last_name' => 'Nombre',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('document_number');
    }

    #[Test]
    public function permite_el_mismo_numero_con_otro_tipo_de_documento(): void
    {
        Person::factory()->create([
            'document_type' => DocumentType::NationalId,
            'document_number' => '12345678',
            'email' => 'uno@example.com',
        ]);

        $this->postJson('/api/persons', [
            'document_type' => DocumentType::Passport->value,
            'document_number' => '12345678',
            'first_name' => 'Otro',
            'last_name' => 'Nombre',
        ])->assertCreated();
    }

    #[Test]
    public function actualiza_una_persona(): void
    {
        $person = Person::factory()->create(['first_name' => 'Antiguo']);

        $this->patchJson("/api/persons/{$person->id}", ['first_name' => 'Nuevo'])
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Nuevo');

        $this->assertSame('Nuevo', $person->fresh()->first_name);
    }

    #[Test]
    public function elimina_y_restaura_una_persona(): void
    {
        $person = Person::factory()->create();

        $this->deleteJson("/api/persons/{$person->id}")->assertNoContent();
        $this->assertSoftDeleted('people', ['id' => $person->id]);

        $this->postJson("/api/persons/{$person->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.deleted_at', null);

        $this->assertNotSoftDeleted('people', ['id' => $person->id]);
    }

    #[Test]
    public function devuelve_404_para_una_persona_inexistente(): void
    {
        $this->getJson('/api/persons/999999')->assertNotFound();
    }

    #[Test]
    public function expone_los_catalogos_del_modulo(): void
    {
        $this->getJson('/api/persons/metadata')
            ->assertOk()
            ->assertJsonStructure(['data' => ['document_types', 'genders', 'sortable']])
            ->assertJsonCount(count(DocumentType::cases()), 'data.document_types');
    }
}
