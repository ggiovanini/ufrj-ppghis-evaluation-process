<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImpersonationController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('impersonate/leave', [ImpersonationController::class, 'leave'])->name('impersonate.leave');
});

require __DIR__.'/settings.php';
require __DIR__.'/team.php';
require __DIR__.'/selection.php';
