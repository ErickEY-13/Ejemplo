<?php

declare(strict_types=1);

namespace App\Modules\Assignments\Database\Seeders;

use App\Modules\Assignments\Models\Site;
use Illuminate\Database\Seeder;

/**
 * Lo ejecuta automáticamente Database\Seeders\DatabaseSeeder.
 *
 * Los códigos coinciden a propósito con App\Modules\Persons\Enums\Site.
 */
class SitesSeeder extends Seeder
{
    /**
     * @var list<array{code: string, name: string}>
     */
    private const SITES = [
        ['code' => 'main', 'name' => 'Sede Central'],
        ['code' => 'north', 'name' => 'Sede Norte'],
        ['code' => 'south', 'name' => 'Sede Sur'],
        ['code' => 'east', 'name' => 'Sede Este'],
        ['code' => 'west', 'name' => 'Sede Oeste'],
        ['code' => 'annex', 'name' => 'Anexo Municipal'],
    ];

    public function run(): void
    {
        if (Site::query()->exists()) {
            $this->command?->info('Módulo Assignments: ya hay sedes, se omite el seeder.');

            return;
        }

        foreach (self::SITES as $site) {
            Site::query()->create($site);
        }

        $this->command?->info('Módulo Assignments: '.count(self::SITES).' sedes creadas.');
    }
}
