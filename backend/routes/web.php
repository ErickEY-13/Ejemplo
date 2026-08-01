<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|---------------------------------------------------------------------------
| Rutas web
|---------------------------------------------------------------------------
|
| El backend es una API REST pura: la interfaz la sirve Angular. Este archivo
| solo expone una pequeña carta de presentación en la raíz.
|
*/

Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'type' => 'api',
    'health' => url('/api/health'),
]))->name('root');
