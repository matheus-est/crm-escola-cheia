<?php

declare(strict_types=1);

use App\Enums\OpportunityStatus;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Grade;
use App\Models\Opportunity;
use App\Models\Outcome;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\Segment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('tenant.school_id');
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function tcMakeSchool(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) ($counter + 70000), 14, '0', STR_PAD_LEFT),
        'legal_name' => "Escola TC {$counter}",
        'slug' => "escola-tc-{$counter}",
    ]);
}

function tcMakeUser(string $roleName, School $school): User
{
    $role = Role::firstOrCreate(
        ['name' => $roleName],
        ['description' => $roleName, 'is_default' => false],
    );

    $user = User::factory()->create();
    $user->role()->associate($role)->save();
    $school->users()->attach($user->id, ['is_active' => true]);

    return $user;
}

function tcMakeOpportunity(School $school, string $status = 'cadastro_inicial'): Opportunity
{
    $segment = Segment::firstOrCreate(
        ['name' => 'Ensino Fundamental'],
        ['name' => 'Ensino Fundamental'],
    );

    $grade = Grade::withoutTenantScope()->firstOrCreate([
        'school_id' => $school->id,
        'segment_id' => $segment->id,
        'name' => '1º Ano',
    ]);

    $schoolYear = SchoolYear::withoutTenantScope()->firstOrCreate(
        ['school_id' => $school->id, 'name' => '2025'],
        [
            'start' => '2025-01-01',
            'end' => '2025-12-31',
            'status' => 'planejamento',
        ]
    );

    return Opportunity::withoutTenantScope()->create([
        'school_id' => $school->id,
        'grade_id' => $grade->id,
        'school_year_id' => $schoolYear->id,
        'status' => $status,
        'renitente_count' => 0,
    ]);
}

function tcMakeOpenTask(Opportunity $opportunity, ?User $assignedUser = null): Task
{
    return Task::withoutTenantScope()->create([
        'school_id' => $opportunity->school_id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Open->value,
        'assigned_user_id' => $assignedUser?->id,
        'due_at' => now()->addHour(),
    ]);
}

function tcMakeOutcome(string $slug, bool $isRefusal = false): Outcome
{
    return Outcome::firstOrCreate(
        ['slug' => $slug],
        [
            'name' => $slug,
            'task_type' => 'retorno_ligacao',
            'is_refusal' => $isRefusal,
            'opens_window' => null,
        ]
    );
}

// ---------------------------------------------------------------------------
// 1. Completing a task marks it as completed
// ---------------------------------------------------------------------------

it('completing a task marks it as completed', function (): void {
    $school = tcMakeSchool();
    $gestor = tcMakeUser('Gestor', $school);
    $opportunity = tcMakeOpportunity($school);
    $task = tcMakeOpenTask($opportunity, $gestor);
    $outcome = tcMakeOutcome('retorno_simples');

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($gestor)
        ->postJson(route('tenant.tasks.complete', $task->uuid), [
            'outcome_uuid' => $outcome->uuid,
        ])
        ->assertStatus(200);

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'status' => TaskStatus::Completed->value,
        'outcome_id' => $outcome->id,
    ]);
});

// ---------------------------------------------------------------------------
// 2. Completing with a refusal outcome saves refusal_category and refusal_detail
// ---------------------------------------------------------------------------

it('completing with refusal outcome saves refusal_category and refusal_detail', function (): void {
    $school = tcMakeSchool();
    $gestor = tcMakeUser('Gestor', $school);
    $opportunity = tcMakeOpportunity($school);
    $task = tcMakeOpenTask($opportunity, $gestor);
    $outcome = tcMakeOutcome('recusa_tc_1', true);

    $outcome->actions()->firstOrCreate(
        ['action_type' => 'move_status'],
        ['payload' => ['status' => 'recusado'], 'order' => 0]
    );

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($gestor)
        ->postJson(route('tenant.tasks.complete', $task->uuid), [
            'outcome_uuid' => $outcome->uuid,
            'refusal_category' => 'questoes_financeiras',
            'refusal_detail' => 'Cliente sem condições financeiras.',
        ])
        ->assertStatus(200);

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'refusal_category' => 'questoes_financeiras',
        'refusal_detail' => 'Cliente sem condições financeiras.',
    ]);
});

// ---------------------------------------------------------------------------
// 3. Completing with refusal outcome missing refusal_category returns 422
// ---------------------------------------------------------------------------

it('completing with refusal outcome missing refusal_category returns 422', function (): void {
    $school = tcMakeSchool();
    $gestor = tcMakeUser('Gestor', $school);
    $opportunity = tcMakeOpportunity($school);
    $task = tcMakeOpenTask($opportunity, $gestor);
    $outcome = tcMakeOutcome('recusa_tc_2', true);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($gestor)
        ->postJson(route('tenant.tasks.complete', $task->uuid), [
            'outcome_uuid' => $outcome->uuid,
            'refusal_detail' => 'Algum detalhe.',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['refusal_category']);
});

// ---------------------------------------------------------------------------
// 4. create_task action creates a new task with correct type
// ---------------------------------------------------------------------------

it('create_task action creates a new task with correct type and due_at', function (): void {
    $school = tcMakeSchool();
    $gestor = tcMakeUser('Gestor', $school);
    $opportunity = tcMakeOpportunity($school);
    $task = tcMakeOpenTask($opportunity, $gestor);

    $outcome = tcMakeOutcome('retorno_cria_tarefa');
    $outcome->actions()->create([
        'action_type' => 'create_task',
        'payload' => ['task_type' => 'agendamento', 'delay_days' => 2],
        'order' => 0,
    ]);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($gestor)
        ->postJson(route('tenant.tasks.complete', $task->uuid), [
            'outcome_uuid' => $outcome->uuid,
        ])
        ->assertStatus(200);

    $this->assertDatabaseHas('tasks', [
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::Agendamento->value,
        'status' => TaskStatus::Open->value,
    ]);
});

// ---------------------------------------------------------------------------
// 5. advance_stage (move_status) updates opportunity status
// ---------------------------------------------------------------------------

it('advance_stage action updates opportunity status', function (): void {
    $school = tcMakeSchool();
    $gestor = tcMakeUser('Gestor', $school);
    $opportunity = tcMakeOpportunity($school);
    $task = tcMakeOpenTask($opportunity, $gestor);

    $outcome = tcMakeOutcome('avancar_etapa');
    $outcome->actions()->create([
        'action_type' => 'move_status',
        'payload' => ['status' => 'visita'],
        'order' => 0,
    ]);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($gestor)
        ->postJson(route('tenant.tasks.complete', $task->uuid), [
            'outcome_uuid' => $outcome->uuid,
        ])
        ->assertStatus(200);

    $this->assertDatabaseHas('opportunities', [
        'id' => $opportunity->id,
        'status' => OpportunityStatus::Visita->value,
    ]);
});

// ---------------------------------------------------------------------------
// 6. cancel_tasks action cancels open tasks of the specified type
// ---------------------------------------------------------------------------

it('cancel_tasks action cancels open tasks of the specified type', function (): void {
    $school = tcMakeSchool();
    $gestor = tcMakeUser('Gestor', $school);
    $opportunity = tcMakeOpportunity($school);

    // Create two tasks: one to complete, one lembrete_agenda to be cancelled
    $mainTask = tcMakeOpenTask($opportunity, $gestor);

    $lembreteTask = Task::withoutTenantScope()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::LembreteAgenda->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->addDay(),
    ]);

    $outcome = tcMakeOutcome('cancela_lembrete');
    $outcome->actions()->create([
        'action_type' => 'cancel_tasks',
        'payload' => ['task_type' => 'lembrete_agenda'],
        'order' => 0,
    ]);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($gestor)
        ->postJson(route('tenant.tasks.complete', $mainTask->uuid), [
            'outcome_uuid' => $outcome->uuid,
        ])
        ->assertStatus(200);

    $this->assertDatabaseHas('tasks', [
        'id' => $lembreteTask->id,
        'status' => TaskStatus::Cancelled->value,
    ]);
});

// ---------------------------------------------------------------------------
// 7. open_window action returns open_window key in JSON response
// ---------------------------------------------------------------------------

it('open_window action returns non-null open_window in JSON response', function (): void {
    $school = tcMakeSchool();
    $gestor = tcMakeUser('Gestor', $school);
    $opportunity = tcMakeOpportunity($school);
    $task = tcMakeOpenTask($opportunity, $gestor);

    $outcome = tcMakeOutcome('abre_janela_agendamento');
    $outcome->actions()->create([
        'action_type' => 'open_window',
        'payload' => ['window' => 'agendamento'],
        'order' => 0,
    ]);

    app()->instance('tenant.school_id', $school->id);

    $response = $this->actingAs($gestor)
        ->postJson(route('tenant.tasks.complete', $task->uuid), [
            'outcome_uuid' => $outcome->uuid,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('open_window', 'agendamento');
});

// ---------------------------------------------------------------------------
// 8. Renitente first attempt: 1h, renitente_count incremented
// ---------------------------------------------------------------------------

it('renitente first attempt schedules task 1 hour from now and increments count', function (): void {
    $school = tcMakeSchool();
    $gestor = tcMakeUser('Gestor', $school);
    $opportunity = tcMakeOpportunity($school);
    $opportunity->update(['renitente_count' => 0]);
    $task = tcMakeOpenTask($opportunity, $gestor);

    $outcome = tcMakeOutcome('retorno_renitente_tc1');
    $outcome->actions()->create([
        'action_type' => 'create_task',
        'payload' => ['task_type' => 'retorno_ligacao', 'renitente' => true],
        'order' => 0,
    ]);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($gestor)
        ->postJson(route('tenant.tasks.complete', $task->uuid), [
            'outcome_uuid' => $outcome->uuid,
        ])
        ->assertStatus(200);

    $newTask = Task::withoutTenantScope()
        ->where('opportunity_id', $opportunity->id)
        ->where('status', TaskStatus::Open->value)
        ->latest()
        ->first();

    expect($newTask)->not->toBeNull();
    expect($newTask->due_at->format('Y-m-d H'))->toBe(now()->addHour()->format('Y-m-d H'));
    expect($opportunity->fresh()->renitente_count)->toBe(1);
});

// ---------------------------------------------------------------------------
// 9. Renitente second attempt: 3h
// ---------------------------------------------------------------------------

it('renitente second attempt schedules task 3 hours from now', function (): void {
    $school = tcMakeSchool();
    $gestor = tcMakeUser('Gestor', $school);
    $opportunity = tcMakeOpportunity($school);
    $opportunity->update(['renitente_count' => 1]);
    $task = tcMakeOpenTask($opportunity, $gestor);

    $outcome = tcMakeOutcome('retorno_renitente_tc2');
    $outcome->actions()->create([
        'action_type' => 'create_task',
        'payload' => ['task_type' => 'retorno_ligacao', 'renitente' => true],
        'order' => 0,
    ]);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($gestor)
        ->postJson(route('tenant.tasks.complete', $task->uuid), [
            'outcome_uuid' => $outcome->uuid,
        ])
        ->assertStatus(200);

    $newTask = Task::withoutTenantScope()
        ->where('opportunity_id', $opportunity->id)
        ->where('status', TaskStatus::Open->value)
        ->latest()
        ->first();

    expect($newTask)->not->toBeNull();
    expect($newTask->due_at->format('Y-m-d H'))->toBe(now()->addHours(3)->format('Y-m-d H'));
    expect($opportunity->fresh()->renitente_count)->toBe(2);
});

// ---------------------------------------------------------------------------
// 10. Renitente at max (count=6) does NOT create new task
// ---------------------------------------------------------------------------

it('renitente at max count does not create new task', function (): void {
    $school = tcMakeSchool();
    $gestor = tcMakeUser('Gestor', $school);
    $opportunity = tcMakeOpportunity($school);
    $opportunity->update(['renitente_count' => 6]);
    $task = tcMakeOpenTask($opportunity, $gestor);

    $outcome = tcMakeOutcome('retorno_renitente_tc3');
    $outcome->actions()->create([
        'action_type' => 'create_task',
        'payload' => ['task_type' => 'retorno_ligacao', 'renitente' => true],
        'order' => 0,
    ]);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($gestor)
        ->postJson(route('tenant.tasks.complete', $task->uuid), [
            'outcome_uuid' => $outcome->uuid,
        ])
        ->assertStatus(200);

    $countAfter = Task::withoutTenantScope()
        ->where('opportunity_id', $opportunity->id)
        ->where('status', TaskStatus::Open->value)
        ->count();

    // No new task should be created (main task completed, no renitente spawned)
    expect($countAfter)->toBe(0);
    // Count should reset to 0
    expect($opportunity->fresh()->renitente_count)->toBe(0);
});

// ---------------------------------------------------------------------------
// 11. Terminal opportunity blocks move_status with appropriate error
// ---------------------------------------------------------------------------

it('completing a task on terminal opportunity with move_status returns 422', function (): void {
    $school = tcMakeSchool();
    $gestor = tcMakeUser('Gestor', $school);
    $opportunity = tcMakeOpportunity($school, 'matricula');

    $task = Task::withoutTenantScope()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::ProvavelMatricula->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->addHour(),
    ]);

    $outcome = tcMakeOutcome('mover_status_terminal');
    $outcome->actions()->create([
        'action_type' => 'move_status',
        'payload' => ['status' => 'recusado'],
        'order' => 0,
    ]);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($gestor)
        ->postJson(route('tenant.tasks.complete', $task->uuid), [
            'outcome_uuid' => $outcome->uuid,
        ])
        ->assertStatus(422);
});

// ---------------------------------------------------------------------------
// 12. All 8 refusal_category values pass validation
// ---------------------------------------------------------------------------

it('all 8 refusal_category values pass validation', function (string $category): void {
    $school = tcMakeSchool();
    $gestor = tcMakeUser('Gestor', $school);
    $opportunity = tcMakeOpportunity($school);
    $task = tcMakeOpenTask($opportunity, $gestor);

    $outcome = Outcome::firstOrCreate(
        ['slug' => 'recusa_all_categories'],
        [
            'name' => 'Recusa All Categories',
            'task_type' => 'retorno_ligacao',
            'is_refusal' => true,
            'opens_window' => null,
        ]
    );

    $outcome->actions()->firstOrCreate(
        ['action_type' => 'move_status'],
        ['payload' => ['status' => 'recusado'], 'order' => 0]
    );

    app()->instance('tenant.school_id', $school->id);

    // Re-open the task for each iteration
    $task->update(['status' => TaskStatus::Open->value, 'outcome_id' => null, 'completed_at' => null]);

    $this->actingAs($gestor)
        ->postJson(route('tenant.tasks.complete', $task->uuid), [
            'outcome_uuid' => $outcome->uuid,
            'refusal_category' => $category,
            'refusal_detail' => 'Detalhe da recusa.',
        ])
        ->assertStatus(200);
})->with([
    'fatores_externos',
    'fatores_internos',
    'pedagogicos',
    'administrativos',
    'sem_interesse',
    'questoes_financeiras',
    'optou_por_concorrente',
    'sem_retorno',
]);
