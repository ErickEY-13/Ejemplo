<?php

declare(strict_types=1);

namespace App\Modules\Persons\Database\Seeders;

use App\Modules\Persons\Models\Person;
use Illuminate\Database\Seeder;

/**
 * Lo ejecuta automáticamente Database\Seeders\DatabaseSeeder.
 */
class PersonsSeeder extends Seeder
{
    public function run(): void
    {
        if (Person::query()->exists()) {
            $this->command?->info('Módulo Personas: ya hay datos, se omite el seeder.');

            return;
        }

        Person::factory()->count(25)->create();
        Person::factory()->count(5)->inactive()->create();

        $this->command?->info('Módulo Personas: 30 registros creados.');
    }
}
