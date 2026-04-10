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

function taskMakeRole(string $name): Role
{
    return Role::firstOrCreate(
        ['name' => $name],
        ['description' => $name, 'is_default' => false],
    );
}

function taskMakeSchool(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) ($counter + 50000), 14, '0', STR_PAD_LEFT),
        'legal_name' => "Escola Task {$counter}",
        'slug' => "escola-task-{$counter}",
    ]);
}

function taskMakeUser(string $roleName, ?School $school = null): User
{
    $role = taskMakeRole($roleName);
    $user = User::factory()->create();
    $user->role()->associate($role)->save();

    if ($school !== null) {
        $school->users()->attach($user->id, ['is_active' => true]);
    }

    return $user;
}

function taskMakeOpportunity(School $school, string $status = 'cadastro_inicial'): Opportunity
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

function taskMakeOpenTask(Opportunity $opportunity, ?User $assignedUser = null): Task
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

function taskMakeOutcome(string $slug = 'retorno_ligacao_renitente'): Outcome
{
    return Outcome::firstOrCreate(
        ['slug' => $slug],
        [
            'name' => $slug,
            'task_type' => 'retorno_ligacao',
            'is_refusal' => false,
            'opens_window' => null,
        ]
    );
}

// ---------------------------------------------------------------------------
// GET /tasks — index
// ---------------------------------------------------------------------------

it('GET index retorna 200 para usuário Master autenticado', function (): void {
    $school = taskMakeSchool();
    $user = taskMakeUser('Master', $school);

    app()->instance('tenant.school_id', $school->id);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('tenant.tasks.index'))
        ->assertStatus(200);
});

it('GET index retorna 302 para usuário não autenticado', function (): void {
    $this->get(route('tenant.tasks.index'))
        ->assertStatus(302);
});

it('GET index retorna 403 para usuário sem permissão (role vazio)', function (): void {
    $school = taskMakeSchool();
    $user = User::factory()->create();
    $school->users()->attach($user->id, ['is_active' => true]);

    app()->instance('tenant.school_id', $school->id);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('tenant.tasks.index'))
        ->assertStatus(403);
});

// ---------------------------------------------------------------------------
// POST /tasks — store
// ---------------------------------------------------------------------------

it('POST store cria uma Task e redireciona', function (): void {
    $school = taskMakeSchool();
    $gestor = taskMakeUser('Gestor', $school);
    $opportunity = taskMakeOpportunity($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($gestor)
        ->post(route('tenant.tasks.store'), [
            'opportunity_uuid' => $opportunity->uuid,
            'type' => TaskType::RetornoLigacao->value,
            'due_at' => now()->addDay()->toDateTimeString(),
        ])
        ->assertRedirect(route('tenant.tasks.index'));

    $this->assertDatabaseHas('tasks', [
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Open->value,
    ]);
});

it('POST store retorna 422 se opportunity_uuid ausente', function (): void {
    $school = taskMakeSchool();
    $gestor = taskMakeUser('Gestor', $school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($gestor)
        ->withHeader('Accept', 'application/json')
        ->post(route('tenant.tasks.store'), [
            'type' => TaskType::RetornoLigacao->value,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['opportunity_uuid']);
});

it('POST store retorna 422 se type ausente', function (): void {
    $school = taskMakeSchool();
    $gestor = taskMakeUser('Gestor', $school);
    $opportunity = taskMakeOpportunity($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($gestor)
        ->withHeader('Accept', 'application/json')
        ->post(route('tenant.tasks.store'), [
            'opportunity_uuid' => $opportunity->uuid,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['type']);
});

it('POST store retorna 422 se oportunidade já tem tarefa aberta', function (): void {
    $school = taskMakeSchool();
    $gestor = taskMakeUser('Gestor', $school);
    $opportunity = taskMakeOpportunity($school);

    taskMakeOpenTask($opportunity, $gestor);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($gestor)
        ->withHeader('Accept', 'application/json')
        ->post(route('tenant.tasks.store'), [
            'opportunity_uuid' => $opportunity->uuid,
            'type' => TaskType::RetornoLigacao->value,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['opportunity_uuid']);
});

it('POST store retorna 403 para usuário sem role de criação de tarefas', function (): void {
    $school = taskMakeSchool();
    // Admin can view but is not listed in create() policy
    $admin = taskMakeUser('Admin', $school);
    $opportunity = taskMakeOpportunity($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($admin)
        ->post(route('tenant.tasks.store'), [
            'opportunity_uuid' => $opportunity->uuid,
            'type' => TaskType::RetornoLigacao->value,
        ])
        ->assertStatus(403);
});

// ---------------------------------------------------------------------------
// POST /tasks/{task}/complete — complete
// ---------------------------------------------------------------------------

it('POST complete tabula a tarefa e retorna JSON com open_window nulo', function (): void {
    $school = taskMakeSchool();
    $gestor = taskMakeUser('Gestor', $school);
    $opportunity = taskMakeOpportunity($school);
    $task = taskMakeOpenTask($opportunity, $gestor);

    $outcome = taskMakeOutcome('retorno_ligacao_renitente');
    $outcome->actions()->create([
        'action_type' => 'create_task',
        'payload' => ['task_type' => 'retorno_ligacao', 'renitente' => true],
        'order' => 0,
    ]);

    app()->instance('tenant.school_id', $school->id);

    $response = $this->actingAs($gestor)
        ->postJson(route('tenant.tasks.complete', $task->uuid), [
            'outcome_uuid' => $outcome->uuid,
        ]);

    $response->assertStatus(200)
        ->assertJson(['open_window' => null]);

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'status' => TaskStatus::Completed->value,
        'outcome_id' => $outcome->id,
    ]);
});

it('POST complete retorna JSON com open_window quando outcome abre janela', function (): void {
    $school = taskMakeSchool();
    $gestor = taskMakeUser('Gestor', $school);
    $opportunity = taskMakeOpportunity($school);
    $task = taskMakeOpenTask($opportunity, $gestor);

    $outcome = Outcome::firstOrCreate(
        ['slug' => 'retorno_ligacao_agendamento'],
        [
            'name' => 'Agendamento',
            'task_type' => 'retorno_ligacao',
            'is_refusal' => false,
            'opens_window' => 'agendamento',
        ]
    );

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
        ->assertJson(['open_window' => 'agendamento']);
});

it('POST complete move status da oportunidade quando action é move_status', function (): void {
    $school = taskMakeSchool();
    $gestor = taskMakeUser('Gestor', $school);
    $opportunity = taskMakeOpportunity($school);
    $task = taskMakeOpenTask($opportunity, $gestor);

    $outcome = Outcome::firstOrCreate(
        ['slug' => 'agendamento_compareceu'],
        [
            'name' => 'Compareceu Agendamento',
            'task_type' => 'agendamento',
            'is_refusal' => false,
            'opens_window' => null,
        ]
    );

    $outcome->actions()->createMany([
        ['action_type' => 'move_status', 'payload' => ['status' => 'visita'], 'order' => 0],
        ['action_type' => 'create_task', 'payload' => ['task_type' => 'provavel_matricula'], 'order' => 1],
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

    $this->assertDatabaseHas('tasks', [
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::ProvavelMatricula->value,
        'status' => TaskStatus::Open->value,
    ]);
});

it('POST complete retorna 422 se task não está aberta', function (): void {
    $school = taskMakeSchool();
    $gestor = taskMakeUser('Gestor', $school);
    $opportunity = taskMakeOpportunity($school);

    $task = Task::withoutTenantScope()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Completed->value,
    ]);

    $outcome = taskMakeOutcome();

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($gestor)
        ->withHeader('Accept', 'application/json')
        ->post(route('tenant.tasks.complete', $task->uuid), [
            'outcome_uuid' => $outcome->uuid,
        ])
        ->assertStatus(422);
});

it('POST complete exige refusal_category e refusal_detail quando outcome é recusa', function (): void {
    $school = taskMakeSchool();
    $gestor = taskMakeUser('Gestor', $school);
    $opportunity = taskMakeOpportunity($school);
    $task = taskMakeOpenTask($opportunity, $gestor);

    $outcome = Outcome::firstOrCreate(
        ['slug' => 'recusa_retorno_ligacao'],
        [
            'name' => 'Recusa',
            'task_type' => 'retorno_ligacao',
            'is_refusal' => true,
            'opens_window' => null,
        ]
    );

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($gestor)
        ->withHeader('Accept', 'application/json')
        ->post(route('tenant.tasks.complete', $task->uuid), [
            'outcome_uuid' => $outcome->uuid,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['refusal_category', 'refusal_detail']);
});

it('POST complete com recusa válida tabula e retorna JSON', function (): void {
    $school = taskMakeSchool();
    $gestor = taskMakeUser('Gestor', $school);
    $opportunity = taskMakeOpportunity($school);
    $task = taskMakeOpenTask($opportunity, $gestor);

    $outcome = Outcome::firstOrCreate(
        ['slug' => 'recusa_retorno_ligacao'],
        [
            'name' => 'Recusa',
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

    $response = $this->actingAs($gestor)
        ->postJson(route('tenant.tasks.complete', $task->uuid), [
            'outcome_uuid' => $outcome->uuid,
            'refusal_category' => 'fatores_externos',
            'refusal_detail' => 'Motivo detalhado da recusa.',
        ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('opportunities', [
        'id' => $opportunity->id,
        'status' => OpportunityStatus::Recusado->value,
    ]);
});

it('POST complete retorna 403 para usuário sem permissão de completar task alheia', function (): void {
    $school = taskMakeSchool();
    $gestor = taskMakeUser('Gestor', $school);
    $comercial = taskMakeUser('Comercial', $school);
    $opportunity = taskMakeOpportunity($school);

    // Task atribuída ao gestor, não ao comercial
    $task = taskMakeOpenTask($opportunity, $gestor);
    $outcome = taskMakeOutcome();

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($comercial)
        ->postJson(route('tenant.tasks.complete', $task->uuid), [
            'outcome_uuid' => $outcome->uuid,
        ])
        ->assertStatus(403);
});

// ---------------------------------------------------------------------------
// POST /tasks/{task}/cancel — cancel
// ---------------------------------------------------------------------------

it('POST cancel cancela a tarefa e redireciona', function (): void {
    $school = taskMakeSchool();
    $gestor = taskMakeUser('Gestor', $school);
    $opportunity = taskMakeOpportunity($school);
    $task = taskMakeOpenTask($opportunity, $gestor);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($gestor)
        ->post(route('tenant.tasks.cancel', $task->uuid))
        ->assertRedirect(route('tenant.tasks.index'));

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'status' => TaskStatus::Cancelled->value,
    ]);
});

it('POST cancel retorna 403 para Comercial (sem permissão)', function (): void {
    $school = taskMakeSchool();
    $comercial = taskMakeUser('Comercial', $school);
    $opportunity = taskMakeOpportunity($school);
    $task = taskMakeOpenTask($opportunity, $comercial);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($comercial)
        ->post(route('tenant.tasks.cancel', $task->uuid))
        ->assertStatus(403);
});

// ---------------------------------------------------------------------------
// Multi-tenancy isolation
// ---------------------------------------------------------------------------

it('Task de outra escola retorna 404 ao tentar completar', function (): void {
    $schoolA = taskMakeSchool();
    $schoolB = taskMakeSchool();

    $gestor = taskMakeUser('Gestor', $schoolB);
    $opportunityA = taskMakeOpportunity($schoolA);
    $taskA = taskMakeOpenTask($opportunityA);
    $outcome = taskMakeOutcome();

    // Active tenant is schoolB
    app()->instance('tenant.school_id', $schoolB->id);

    $this->actingAs($gestor)
        ->postJson(route('tenant.tasks.complete', $taskA->uuid), [
            'outcome_uuid' => $outcome->uuid,
        ])
        ->assertStatus(404);
});
