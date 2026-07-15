<?php

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
});
