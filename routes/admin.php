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

        Route::match(['get', 'post'], '/schools', [SchoolController::class, 'index'])->name('schools.index');
        Route::get('/schools/clearFilters', [SchoolController::class, 'clearFilters'])->name('schools.clearFilters');
        Route::get('/schools/create', [SchoolController::class, 'create'])->name('schools.create');
        Route::post('/schools', [SchoolController::class, 'store'])->name('schools.store');
        Route::get('/schools/{school}', [SchoolController::class, 'show'])->name('schools.show');
        Route::get('/schools/{school}/edit', [SchoolController::class, 'edit'])->name('schools.edit');
        Route::put('/schools/{school}', [SchoolController::class, 'update'])->name('schools.update');
        Route::patch('/schools/{school}', [SchoolController::class, 'update']);
        Route::delete('/schools/{school}', [SchoolController::class, 'destroy'])->name('schools.destroy');
        Route::post('/schools/{school}/confirmDelete', [SchoolController::class, 'confirmDelete'])->name('schools.confirmDelete');

        Route::post('schools/{school}/users', [SchoolUserController::class, 'store'])
            ->name('schools.users.store');

        Route::delete('schools/{school}/users/{userUuid}', [SchoolUserController::class, 'destroy'])
            ->name('schools.users.destroy');
    });
