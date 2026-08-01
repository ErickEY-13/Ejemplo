<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Ejecuta el seeder de cada módulo.
 *
 * Los seeders se descubren solos, así que añadir un módulo nuevo no obliga
 * a tocar este archivo (que es compartido por todo el equipo).
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        foreach ($this->discoverModuleSeeders() as $seeder) {
            $this->call($seeder);
        }
    }

    /**
     * Busca app/Modules/{Modulo}/Database/Seeders/*Seeder.php
     *
     * @return list<class-string<Seeder>>
     */
    protected function discoverModuleSeeders(): array
    {
        $appPath = str_replace('\\', '/', app_path()).'/';
        $pattern = $appPath.'Modules/*/Database/Seeders/*Seeder.php';

        $seeders = [];

        foreach (glob($pattern) ?: [] as $file) {
            $relative = substr(str_replace('\\', '/', $file), strlen($appPath), -strlen('.php'));
            $class = 'App\\'.str_replace('/', '\\', $relative);

            if (class_exists($class) && is_subclass_of($class, Seeder::class)) {
                $seeders[] = $class;
            }
        }

        sort($seeders);

        return $seeders;
    }
}
