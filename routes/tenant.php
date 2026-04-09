<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\EventController;
use App\Http\Controllers\Tenant\GuardianController;
use App\Http\Controllers\Tenant\OpportunityController;
use App\Http\Controllers\Tenant\Settings\GradeController;
use App\Http\Controllers\Tenant\Settings\LeadSourceController;
use App\Http\Controllers\Tenant\Settings\RoomController;
use App\Http\Controllers\Tenant\Settings\SchoolYearController;
use App\Http\Controllers\Tenant\StudentController;
use App\Http\Controllers\Tenant\TaskController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'tenant', 'tenant.access'])
    ->prefix('tenant')
    ->name('tenant.')
    ->group(function (): void {
        Route::get('/dashboard', fn () => Inertia::render('Dashboard'))
            ->name('dashboard');

        // Tenant Settings — Ano Letivo
        Route::match(['get', 'post'], '/tenant-settings/school-years', [SchoolYearController::class, 'index'])
            ->name('school_years.index');
        Route::post('/tenant-settings/school-years/store', [SchoolYearController::class, 'store'])
            ->name('school_years.store');
        Route::put('/tenant-settings/school-years/{schoolYear}', [SchoolYearController::class, 'update'])
            ->name('school_years.update');
        Route::delete('/tenant-settings/school-years/{schoolYear}', [SchoolYearController::class, 'destroy'])
            ->name('school_years.destroy');

        // Tenant Settings — Origem de Lead
        Route::match(['get', 'post'], '/tenant-settings/lead-sources', [LeadSourceController::class, 'index'])
            ->name('lead_sources.index');
        Route::post('/tenant-settings/lead-sources/store', [LeadSourceController::class, 'store'])
            ->name('lead_sources.store');
        Route::put('/tenant-settings/lead-sources/{leadSource}', [LeadSourceController::class, 'update'])
            ->name('lead_sources.update');
        Route::delete('/tenant-settings/lead-sources/{leadSource}', [LeadSourceController::class, 'destroy'])
            ->name('lead_sources.destroy');

        // Tenant Settings — Turmas e Séries
        Route::match(['get', 'post'], '/tenant-settings/grades', [GradeController::class, 'index'])
            ->name('grades.index');
        Route::post('/tenant-settings/grades/store', [GradeController::class, 'store'])
            ->name('grades.store');
        Route::put('/tenant-settings/grades/{grade}', [GradeController::class, 'update'])
            ->name('grades.update');
        Route::delete('/tenant-settings/grades/{grade}', [GradeController::class, 'destroy'])
            ->name('grades.destroy');

        // Tenant Settings — Salas
        // Salas
        Route::match(['get', 'post'], '/tenant-settings/rooms', [RoomController::class, 'index'])
            ->name('settings.rooms.index');
        Route::post('/tenant-settings/rooms', [RoomController::class, 'store'])
            ->name('settings.rooms.store');
        Route::put('/tenant-settings/rooms/{room}', [RoomController::class, 'update'])
            ->name('settings.rooms.update');
        Route::delete('/tenant-settings/rooms/{room}', [RoomController::class, 'destroy'])
            ->name('settings.rooms.destroy');

        // Alunos
        Route::match(['get', 'post'], '/students', [StudentController::class, 'index'])
            ->name('students.index');
        Route::post('/students/store', [StudentController::class, 'store'])
            ->name('students.store');
        Route::get('/students/lookup/{cpf}', [StudentController::class, 'lookup'])
            ->name('students.lookup');
        Route::put('/students/{student}', [StudentController::class, 'update'])
            ->name('students.update');
        Route::delete('/students/{student}', [StudentController::class, 'destroy'])
            ->name('students.destroy');

        // Responsáveis
        Route::match(['get', 'post'], '/guardians', [GuardianController::class, 'index'])
            ->name('guardians.index');
        Route::post('/guardians/store', [GuardianController::class, 'store'])
            ->name('guardians.store');
        Route::get('/guardians/lookup/{cpf}', [GuardianController::class, 'lookup'])
            ->name('guardians.lookup');
        Route::get('/guardians/validate-cpf/{cpf}', [GuardianController::class, 'validateCpf'])
            ->name('guardians.validate_cpf');
        Route::put('/guardians/{guardian}', [GuardianController::class, 'update'])
            ->name('guardians.update');
        Route::delete('/guardians/{guardian}', [GuardianController::class, 'destroy'])
            ->name('guardians.destroy');

        // Oportunidades
        Route::match(['get', 'post'], '/opportunities', [OpportunityController::class, 'index'])
            ->name('opportunities.index');
        Route::get('/opportunities/create', [OpportunityController::class, 'create'])
            ->name('opportunities.create');
        Route::post('/opportunities', [OpportunityController::class, 'store'])
            ->name('opportunities.store');
        Route::get('/opportunities/{opportunity}', [OpportunityController::class, 'show'])
            ->name('opportunities.show');
        Route::get('/opportunities/{opportunity}/edit', [OpportunityController::class, 'edit'])
            ->name('opportunities.edit');
        Route::put('/opportunities/{opportunity}', [OpportunityController::class, 'update'])
            ->name('opportunities.update');
        Route::delete('/opportunities/{opportunity}', [OpportunityController::class, 'destroy'])
            ->name('opportunities.destroy');

        // Tarefas
        Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
        Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::post('/tasks/{task}/complete', [TaskController::class, 'complete'])->name('tasks.complete');
        Route::post('/tasks/{task}/cancel', [TaskController::class, 'cancel'])->name('tasks.cancel');

        // Eventos
        Route::get('/events/available', [EventController::class, 'available'])->name('events.available');
        Route::match(['get', 'post'], '/events', [EventController::class, 'index'])->name('events.index');
        Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
        Route::post('/events', [EventController::class, 'store'])->name('events.store');
        Route::get('/events/{event}/available-opportunities', [EventController::class, 'availableOpportunities'])->name('events.available_opportunities');
        Route::get('/events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
        Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update');
        Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy');
        Route::post('/events/{event}/opportunities', [EventController::class, 'attachOpportunity'])->name('events.opportunities.attach');
        Route::delete('/events/{event}/opportunities/{opportunity}', [EventController::class, 'detachOpportunity'])->name('events.opportunities.detach');
    });
