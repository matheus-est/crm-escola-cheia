<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\GuardianController;
use App\Http\Controllers\Tenant\OpportunityController;
use App\Http\Controllers\Tenant\Settings\LeadSourceController;
use App\Http\Controllers\Tenant\Settings\SchoolYearController;
use App\Http\Controllers\Tenant\StudentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'tenant', 'tenant.access'])
    ->prefix('t/{school_uuid}')
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
        Route::get('/opportunities/{opportunity}/edit', [OpportunityController::class, 'edit'])
            ->name('opportunities.edit');
        Route::put('/opportunities/{opportunity}', [OpportunityController::class, 'update'])
            ->name('opportunities.update');
        Route::delete('/opportunities/{opportunity}', [OpportunityController::class, 'destroy'])
            ->name('opportunities.destroy');

        // TODO Etapa 2.x — Tarefas
        // Route::resource('tarefas', TarefaController::class);

        // TODO Etapa 2.x — Leads
        // Route::resource('leads', LeadController::class);

        // TODO Etapa 3.x — Configurações do tenant
        // Route::get('/configuracoes', [TenantSettingsController::class, 'index'])->name('configuracoes.index');
    });
