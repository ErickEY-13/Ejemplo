<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Database\Seeders;

use App\Modules\Vehicles\Models\Vehicle;
use Illuminate\Database\Seeder;

/**
 * Lo ejecuta automáticamente Database\Seeders\DatabaseSeeder.
 */
class VehiclesSeeder extends Seeder
{
    public function run(): void
    {
        if (Vehicle::query()->exists()) {
            $this->command?->info('Módulo Vehículos: ya hay datos, se omite el seeder.');

            return;
        }

        Vehicle::factory()->count(25)->create();
        Vehicle::factory()->count(5)->inactive()->create();

        $this->command?->info('Módulo Vehículos: 30 registros creados.');
    }
}
