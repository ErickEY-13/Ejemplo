<?php

declare(strict_types=1);

/*
|---------------------------------------------------------------------------
| Registro de módulos
|---------------------------------------------------------------------------
|
| Metadatos que el menú principal del frontend puede consumir vía
| GET /api/modules. El código de cada módulo vive en app/Modules/{Nombre};
| aquí solo se describe de cara a la interfaz.
|
*/

return [

    'registry' => [

        'persons' => [
            'key' => 'persons',
            'name' => 'Registro de Personas',
            'description' => 'Alta, consulta y mantenimiento de personas.',
            'icon' => 'users',
            'path' => '/personas',
            'api' => '/api/persons',
        ],

        'vehicles' => [
            'key' => 'vehicles',
            'name' => 'Registro de Vehículos',
            'description' => 'Alta, consulta y mantenimiento de vehículos.',
            'icon' => 'car',
            'path' => '/vehiculos',
            'api' => '/api/vehicles',
        ],

    ],

];
