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
Route::get('export/excel', [PersonController::class, 'exportExcel'])->name('export.excel');
Route::get('export/pdf', [PersonController::class, 'exportPdf'])->name('export.pdf');

Route::post('import/csv', [PersonController::class, 'importCsv'])->name('import.csv');
Route::get('import/csv/{jobId}/status', [PersonController::class, 'importStatus'])->name('import.status');

Route::get('/', [PersonController::class, 'index'])->name('index');
Route::post('/', [PersonController::class, 'store'])->name('store');
Route::get('{person}', [PersonController::class, 'show'])->name('show');
Route::match(['put', 'patch'], '{person}', [PersonController::class, 'update'])->name('update');
Route::delete('{person}', [PersonController::class, 'destroy'])->name('destroy');
Route::post('{person}/restore', [PersonController::class, 'restore'])
    ->withTrashed()
    ->name('restore');

// Foto de perfil
Route::post('{person}/photo', [PersonController::class, 'uploadPhoto'])->name('photo.store');
Route::delete('{person}/photo', [PersonController::class, 'destroyPhoto'])->name('photo.destroy');

// Documentos adjuntos
Route::post('{person}/documents', [PersonController::class, 'storeDocument'])->name('documents.store');
Route::delete('{person}/documents/{document}', [PersonController::class, 'destroyDocument'])->name('documents.destroy');

// Extras (Entrevista)
Route::get('{person}/audits', [PersonController::class, 'audits'])->name('audits');
Route::get('{person}/pdf', [PersonController::class, 'pdf'])->name('pdf');

