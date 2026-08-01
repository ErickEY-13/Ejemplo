<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Database\Factories;

use App\Modules\Vehicles\Enums\FuelType;
use App\Modules\Vehicles\Enums\VehicleType;
use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    protected $model = Vehicle::class;

    /**
     * @var array<string, list<string>>
     */
    private const CATALOG = [
        'Toyota' => ['Corolla', 'Hilux', 'Yaris', 'RAV4'],
        'Nissan' => ['Sentra', 'Frontier', 'Versa', 'X-Trail'],
        'Hyundai' => ['Accent', 'Tucson', 'Elantra', 'Santa Fe'],
        'Kia' => ['Rio', 'Sportage', 'Picanto', 'Seltos'],
        'Volkswagen' => ['Gol', 'Amarok', 'Polo', 'T-Cross'],
        'Chevrolet' => ['Onix', 'Sail', 'Tracker', 'D-Max'],
    ];

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $brand = fake()->randomElement(array_keys(self::CATALOG));

        return [
            'plate' => strtoupper(fake()->unique()->bothify('???-###')),
            'brand' => $brand,
            'model' => fake()->randomElement(self::CATALOG[$brand]),
            'year' => fake()->numberBetween(2005, (int) date('Y')),
            'type' => fake()->randomElement(VehicleType::cases()),
            'fuel_type' => fake()->randomElement(FuelType::cases()),
            'color' => fake()->randomElement(['Blanco', 'Negro', 'Gris', 'Rojo', 'Azul', 'Plata']),
            'vin' => strtoupper(fake()->unique()->bothify('#################')),
            'engine_number' => strtoupper(fake()->bothify('??######')),
            'mileage' => fake()->numberBetween(0, 250_000),
            'is_active' => true,
            'notes' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
