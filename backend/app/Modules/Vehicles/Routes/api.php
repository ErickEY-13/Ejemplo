<?php

declare(strict_types=1);

use App\Modules\Vehicles\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

/*
|---------------------------------------------------------------------------
| Rutas del módulo de Vehículos
|---------------------------------------------------------------------------
|
| Se publican automáticamente bajo el prefijo /api/vehicles con el grupo de
| middleware "api" (ver App\Support\Module\ModuleServiceProvider).
|
| Este archivo pertenece al Desarrollador 2: nadie más debería editarlo.
|
*/

Route::get('metadata', [VehicleController::class, 'metadata'])->name('metadata');

Route::get('/', [VehicleController::class, 'index'])->name('index');
Route::post('/', [VehicleController::class, 'store'])->name('store');
Route::get('{vehicle}', [VehicleController::class, 'show'])->name('show');
Route::match(['put', 'patch'], '{vehicle}', [VehicleController::class, 'update'])->name('update');
Route::delete('{vehicle}', [VehicleController::class, 'destroy'])->name('destroy');
Route::post('{vehicle}/restore', [VehicleController::class, 'restore'])
    ->withTrashed()
    ->name('restore');
