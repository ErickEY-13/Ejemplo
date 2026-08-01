<?php

declare(strict_types=1);

/*
|---------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS)
|---------------------------------------------------------------------------
|
| En desarrollo el SPA vive en http://localhost:4200 y la API en :8000, por lo
| que hacen falta cabeceras CORS. En producción ambos se sirven desde el mismo
| Nginx (mismo origen) y estas reglas dejan de aplicarse.
|
*/

return [

    'paths' => ['api/*', 'up'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', env('FRONTEND_URL', 'http://localhost:4200')))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 3600,

    // La API es stateless (sin cookies de sesión), no hacen falta credenciales.
    'supports_credentials' => false,

];
