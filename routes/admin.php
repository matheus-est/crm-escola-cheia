<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\SchoolController;
use App\Http\Controllers\Admin\SchoolUserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'role:Master,Admin,Operacao'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/dashboard', fn () => Inertia::render('Dashboard'))
            ->name('dashboard');

        Route::resource('schools', SchoolController::class);

        Route::post('schools/{school}/users', [SchoolUserController::class, 'store'])
            ->name('schools.users.store');

        Route::delete('schools/{school}/users/{userUuid}', [SchoolUserController::class, 'destroy'])
            ->name('schools.users.destroy');
    });
