<?php

declare(strict_types=1);

namespace Tests\Feature\Vehicles;

use App\Modules\Vehicles\Enums\FuelType;
use App\Modules\Vehicles\Enums\VehicleType;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VehicleApiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function lista_vehiculos_paginados(): void
    {
        Vehicle::factory()->count(3)->create();

        $this->getJson('/api/vehicles')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'plate', 'description', 'year', 'is_active']],
                'meta' => ['current_page', 'total', 'per_page'],
            ]);
    }

    #[Test]
    public function filtra_por_tipo_y_por_rango_de_anos(): void
    {
        Vehicle::factory()->create(['type' => VehicleType::Motorcycle, 'year' => 2010]);
        Vehicle::factory()->create(['type' => VehicleType::Car, 'year' => 2022]);

        $this->getJson('/api/vehicles?type=car')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'car');

        $this->getJson('/api/vehicles?year_from=2015')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.year', 2022);
    }

    #[Test]
    public function crea_un_vehiculo_y_normaliza_la_placa(): void
    {
        // Sin `fuel_type` ni `mileage`: deben aplicarse los valores por defecto.
        $this->postJson('/api/vehicles', [
            'plate' => 'abc-123',
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2020,
            'type' => VehicleType::Car->value,
        ])
            ->assertCreated()
            ->assertJsonPath('data.plate', 'ABC-123')
            ->assertJsonPath('data.description', 'Toyota Corolla (2020)')
            ->assertJsonPath('data.fuel_type', FuelType::Gasoline->value)
            ->assertJsonPath('data.mileage', 0);

        $this->assertDatabaseHas('vehicles', ['plate' => 'ABC-123']);
    }

    #[Test]
    public function rechaza_placas_duplicadas(): void
    {
        Vehicle::factory()->create(['plate' => 'XYZ-987']);

        $this->postJson('/api/vehicles', [
            'plate' => 'xyz-987',
            'brand' => 'Nissan',
            'model' => 'Sentra',
            'year' => 2019,
            'type' => VehicleType::Car->value,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('plate');
    }

    #[Test]
    public function rechaza_un_ano_futuro_fuera_de_rango(): void
    {
        $this->postJson('/api/vehicles', [
            'plate' => 'AAA-111',
            'brand' => 'Kia',
            'model' => 'Rio',
            'year' => ((int) date('Y')) + 5,
            'type' => VehicleType::Car->value,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('year');
    }

    #[Test]
    public function actualiza_un_vehiculo(): void
    {
        $vehicle = Vehicle::factory()->create(['mileage' => 1000]);

        $this->patchJson("/api/vehicles/{$vehicle->id}", ['mileage' => 45000])
            ->assertOk()
            ->assertJsonPath('data.mileage', 45000);

        $this->assertSame(45000, $vehicle->fresh()->mileage);
    }

    #[Test]
    public function elimina_y_restaura_un_vehiculo(): void
    {
        $vehicle = Vehicle::factory()->create();

        $this->deleteJson("/api/vehicles/{$vehicle->id}")->assertNoContent();
        $this->assertSoftDeleted('vehicles', ['id' => $vehicle->id]);

        $this->postJson("/api/vehicles/{$vehicle->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.deleted_at', null);

        $this->assertNotSoftDeleted('vehicles', ['id' => $vehicle->id]);
    }

    #[Test]
    public function devuelve_404_para_un_vehiculo_inexistente(): void
    {
        $this->getJson('/api/vehicles/999999')->assertNotFound();
    }

    #[Test]
    public function expone_los_catalogos_del_modulo(): void
    {
        Vehicle::factory()->create(['brand' => 'Toyota']);

        $this->getJson('/api/vehicles/metadata')
            ->assertOk()
            ->assertJsonStructure(['data' => ['types', 'fuel_types', 'brands', 'sortable', 'year_range']])
            ->assertJsonPath('data.brands', ['Toyota']);
    }
}
