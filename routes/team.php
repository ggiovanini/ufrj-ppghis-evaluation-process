<?php

use App\Http\Controllers\Team\TeamController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('team')
    ->name('team')
    ->group(function () {
    Route::get('/', [TeamController::class, 'index'])->name('.index');
    Route::get('/role/{role}', [TeamController::class, 'role'])->name('.role');
    Route::get('/create', [TeamController::class, 'create'])->name('.create');
    Route::post('/', [TeamController::class, 'store'])->name('.store');
    Route::get('/{user}', [TeamController::class, 'show'])->name('.show');
    Route::get('/{user}/edit', [TeamController::class, 'edit'])->name('.edit');
    Route::patch('/{user}', [TeamController::class, 'update'])->name('.update');
    Route::delete('/{user}', [TeamController::class, 'destroy'])->name('.delete');
});
