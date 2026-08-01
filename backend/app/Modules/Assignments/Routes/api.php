<?php

declare(strict_types=1);

use App\Modules\Assignments\Http\Controllers\AssignmentController;
use Illuminate\Support\Facades\Route;

/*
|---------------------------------------------------------------------------
| Rutas del módulo de Assignments
|---------------------------------------------------------------------------
|
| Se publican automáticamente bajo el prefijo /api/assignments con el grupo
| de middleware "api" (ver App\Support\Module\ModuleServiceProvider).
|
| Este módulo es el único puente entre Vehículos y Personas.
|
*/

Route::get('people', [AssignmentController::class, 'people'])->name('people');

Route::get('{vehicle}', [AssignmentController::class, 'show'])->name('show');
Route::match(['put', 'patch'], '{vehicle}', [AssignmentController::class, 'store'])->name('store');
Route::delete('{vehicle}', [AssignmentController::class, 'destroy'])->name('destroy');
