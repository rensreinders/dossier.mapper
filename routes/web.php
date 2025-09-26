<?php

use App\Http\Controllers\MappingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MappingController::class, 'index'])->name('mapping.index');

Route::post('/', [MappingController::class, 'update'])->name('mapping.update');

Route::get('/import', [MappingController::class, 'import'])->name('mapping.import');
Route::post('/import', [MappingController::class, 'processImport'])->name('mapping.import.process');
Route::get('/download', [MappingController::class, 'download'])->name('mapping.download');
