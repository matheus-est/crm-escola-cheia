<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'role:Master,Admin,Operacao'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/dashboard', fn () => Inertia::render('Dashboard'))
            ->name('dashboard');

        // TODO Etapa 1.x — SchoolController (CRUD de escolas)
        // Route::resource('schools', SchoolController::class);

        // TODO Etapa 1.x — SchoolUserController (vínculo usuário-escola)
        // Route::post('schools/{school}/users', [SchoolUserController::class, 'store'])->name('schools.users.store');
        // Route::delete('schools/{school}/users/{user}', [SchoolUserController::class, 'destroy'])->name('schools.users.destroy');
    });
