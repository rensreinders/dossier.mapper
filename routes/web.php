<?php

use App\Http\Controllers\MappingController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;


Route::group(['middleware' => ['auth']], function () {
    Route::get('/', [MappingController::class, 'index'])->name('mapping.index');
    Route::get('/dashboard', [MappingController::class, 'index'])->name('dashboard');

    Route::post('/', [MappingController::class, 'update'])->name('mapping.update');

    Route::get('/import', [MappingController::class, 'import'])->name('mapping.import');
    Route::post('/import', [MappingController::class, 'processImport'])->name('mapping.import.process');
    Route::get('/download', [MappingController::class, 'download'])->name('mapping.download');
});

require __DIR__.'/auth.php';
