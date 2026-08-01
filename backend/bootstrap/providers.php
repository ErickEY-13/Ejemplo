<?php

use App\Providers\AppServiceProvider;
use App\Providers\ModuleRegistrationServiceProvider;

return [
    AppServiceProvider::class,
    // Descubre y registra automáticamente todo lo que haya en app/Modules.
    ModuleRegistrationServiceProvider::class,
];
