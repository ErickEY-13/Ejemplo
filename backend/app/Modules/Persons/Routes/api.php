<?php

declare(strict_types=1);

use App\Modules\Persons\Http\Controllers\PersonController;
use Illuminate\Support\Facades\Route;

/*
|---------------------------------------------------------------------------
| Rutas del módulo de Personas
|---------------------------------------------------------------------------
|
| Se publican automáticamente bajo el prefijo /api/persons con el grupo de
| middleware "api" (ver App\Support\Module\ModuleServiceProvider).
|
| Este archivo pertenece al Desarrollador 1: nadie más debería editarlo.
|
*/

Route::get('metadata', [PersonController::class, 'metadata'])->name('metadata');

Route::get('/', [PersonController::class, 'index'])->name('index');
Route::post('/', [PersonController::class, 'store'])->name('store');
Route::get('{person}', [PersonController::class, 'show'])->name('show');
Route::match(['put', 'patch'], '{person}', [PersonController::class, 'update'])->name('update');
Route::delete('{person}', [PersonController::class, 'destroy'])->name('destroy');
Route::post('{person}/restore', [PersonController::class, 'restore'])
    ->withTrashed()
    ->name('restore');
