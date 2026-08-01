<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|---------------------------------------------------------------------------
| Rutas transversales de la API
|---------------------------------------------------------------------------
|
| IMPORTANTE: este archivo es compartido por todo el equipo. Las rutas de
| cada módulo NO se declaran aquí, sino en:
|
|     app/Modules/{Modulo}/Routes/api.php
|
| y se publican automáticamente bajo /api/{prefijo-del-modulo}.
|
*/

Route::get('/health', function () {
    $database = 'ok';

    try {
        DB::connection()->getPdo();
    } catch (\Throwable $e) {
        $database = 'error';
    }

    return response()->json([
        'status' => $database === 'ok' ? 'ok' : 'degraded',
        'app' => config('app.name'),
        'environment' => config('app.env'),
        'database' => $database,
        'timestamp' => now()->toIso8601String(),
    ], $database === 'ok' ? 200 : 503);
})->name('health');

/**
 * Devuelve los módulos disponibles. El menú principal de Angular puede
 * consumirlo para construirse de forma dinámica.
 */
Route::get('/modules', function () {
    return response()->json([
        'data' => array_values(config('modules.registry', [])),
    ]);
})->name('modules.index');
