<?php

use App\Http\Controllers\Projects\ProjectsController;
use App\Http\Controllers\SelectionProcess\SelectionProcessController;
use App\Http\Controllers\Team\TeamController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('selection')
    ->name('selection')
    ->group(function () {
    Route::get('/', [SelectionProcessController::class, 'index'])->name('.index');
    Route::post('/', [SelectionProcessController::class, 'store'])->name('.store');
    Route::get('/{selection}', [SelectionProcessController::class, 'show'])->name('.show');
    Route::get('/{selection}/edit', [SelectionProcessController::class, 'edit'])->name('.edit');
    Route::patch('/{selection}', [SelectionProcessController::class, 'update'])->name('.update');
    Route::delete('/{selection}', [SelectionProcessController::class, 'destroy'])->name('.delete');
    Route::post('/{selection}/import', [SelectionProcessController::class, 'import'])->name('.import');

    Route::prefix('/{selection}/projects')
        ->name('.projects')
        ->group(function () {
        Route::get('/', [ProjectsController::class, 'index'])->name('.index');
        Route::get('/{project}', [ProjectsController::class, 'show'])->name('.show');
        Route::get('/{project}/edit', [ProjectsController::class, 'edit'])->name('.edit');
        Route::patch('/{project}', [ProjectsController::class, 'update'])->name('.update');
        Route::delete('/{project}', [ProjectsController::class, 'destroy'])->name('.delete');
    });
});
