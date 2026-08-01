<?php

declare(strict_types=1);

namespace App\Modules\Persons\Providers;

use App\Support\Module\ModuleServiceProvider;

/**
 * Punto de entrada del módulo de Personas.
 *
 * Lo descubre automáticamente App\Providers\ModuleRegistrationServiceProvider:
 * no hay que registrarlo en ningún archivo compartido.
 */
class PersonsServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Persons';
    }
}
