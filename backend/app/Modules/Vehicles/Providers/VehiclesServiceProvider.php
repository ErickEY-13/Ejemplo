<?php

declare(strict_types=1);

namespace App\Modules\Vehicles\Providers;

use App\Support\Module\ModuleServiceProvider;

/**
 * Punto de entrada del módulo de Vehículos.
 *
 * Lo descubre automáticamente App\Providers\ModuleRegistrationServiceProvider:
 * no hay que registrarlo en ningún archivo compartido.
 */
class VehiclesServiceProvider extends ModuleServiceProvider
{
    protected function name(): string
    {
        return 'Vehicles';
    }
}
