<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware(['auth', 'verified', 'tenant', 'tenant.access'])
    ->prefix('t/{school_uuid}')
    ->name('tenant.')
    ->group(function (): void {
        Route::get('/dashboard', fn () => Inertia::render('Dashboard'))
            ->name('dashboard');

        // TODO Etapa 2.x — Oportunidades
        // Route::resource('oportunidades', OportunidadeController::class);

        // TODO Etapa 2.x — Tarefas
        // Route::resource('tarefas', TarefaController::class);

        // TODO Etapa 2.x — Leads
        // Route::resource('leads', LeadController::class);

        // TODO Etapa 3.x — Alunos
        // Route::resource('alunos', AlunoController::class);

        // TODO Etapa 3.x — Configurações do tenant
        // Route::get('/configuracoes', [TenantSettingsController::class, 'index'])->name('configuracoes.index');
    });
