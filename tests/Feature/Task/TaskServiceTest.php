<?php

declare(strict_types=1);

use App\Enums\OpportunityStatus;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Http\Resources\TaskResource;
use App\Models\Grade;
use App\Models\Opportunity;
use App\Models\Outcome;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\Segment;
use App\Models\Task;
use App\Services\Task\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('tenant.school_id');
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function svcMakeSchool(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) ($counter + 80000), 14, '0', STR_PAD_LEFT),
        'legal_name' => "Escola Svc {$counter}",
        'slug' => "escola-svc-{$counter}",
    ]);
}

function svcMakeOpportunity(School $school): Opportunity
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
        'status' => OpportunityStatus::CadastroInicial->value,
        'renitente_count' => 0,
    ]);
}

function svcMakeOpenTask(Opportunity $opportunity): Task
{
    return Task::withoutTenantScope()->create([
        'school_id' => $opportunity->school_id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->addHour(),
    ]);
}

function svcMakeOutcome(bool $isRefusal = false): Outcome
{
    $slug = $isRefusal ? 'recusa_svc_test' : 'retorno_ligacao_svc_test';

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
// Tests: refusal fields in complete()
// ---------------------------------------------------------------------------

it('completing a task with refusal data stores refusal_category and refusal_detail', function (): void {
    $school = svcMakeSchool();
    app()->instance('tenant.school_id', $school->id);

    $opportunity = svcMakeOpportunity($school);
    $task = svcMakeOpenTask($opportunity);
    $outcome = svcMakeOutcome(true);

    $service = app(TaskService::class);
    $service->complete($task, $outcome, [
        'refusal_category' => 'financeiro',
        'refusal_detail' => 'Não tem condições financeiras.',
    ]);

    $task->refresh();
    expect($task->refusal_category)->toBe('financeiro');
    expect($task->refusal_detail)->toBe('Não tem condições financeiras.');
});

it('completing a task without refusal leaves both columns null', function (): void {
    $school = svcMakeSchool();
    app()->instance('tenant.school_id', $school->id);

    $opportunity = svcMakeOpportunity($school);
    $task = svcMakeOpenTask($opportunity);
    $outcome = svcMakeOutcome(false);

    $service = app(TaskService::class);
    $service->complete($task, $outcome, ['notes' => 'Nota qualquer']);

    $task->refresh();
    expect($task->refusal_category)->toBeNull();
    expect($task->refusal_detail)->toBeNull();
});

it('TaskResource exposes refusal_category and refusal_detail', function (): void {
    $school = svcMakeSchool();
    app()->instance('tenant.school_id', $school->id);

    $opportunity = svcMakeOpportunity($school);
    $task = Task::withoutTenantScope()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Completed->value,
        'refusal_category' => 'localizacao',
        'refusal_detail' => 'Escola muito longe.',
        'due_at' => now()->addHour(),
    ]);

    $resource = (new TaskResource($task))->toArray(Request::create('/'));

    expect($resource)->toHaveKey('refusal_category', 'localizacao');
    expect($resource)->toHaveKey('refusal_detail', 'Escola muito longe.');
});

// ---------------------------------------------------------------------------
// Tests: list() filters
// ---------------------------------------------------------------------------

it('date_from filter returns only tasks on or after that date', function (): void {
    $school = svcMakeSchool();
    app()->instance('tenant.school_id', $school->id);

    $opportunity = svcMakeOpportunity($school);

    Task::withoutTenantScope()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->subDays(2),
    ]);

    Task::withoutTenantScope()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->addDay(),
    ]);

    $service = app(TaskService::class);
    $result = $service->list(['date_from' => now()->startOfDay()->toDateTimeString()]);

    expect($result->total())->toBe(1);
});

it('date_to filter returns only tasks on or before that date', function (): void {
    $school = svcMakeSchool();
    app()->instance('tenant.school_id', $school->id);

    $opportunity = svcMakeOpportunity($school);

    Task::withoutTenantScope()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->subDay(),
    ]);

    Task::withoutTenantScope()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->addDays(5),
    ]);

    $service = app(TaskService::class);
    $result = $service->list(['date_to' => now()->addDays(2)->toDateTimeString()]);

    expect($result->total())->toBe(1);
});

it('date_from and date_to range works correctly', function (): void {
    $school = svcMakeSchool();
    app()->instance('tenant.school_id', $school->id);

    $opportunity = svcMakeOpportunity($school);

    // Outside range (too early)
    Task::withoutTenantScope()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->subDays(10),
    ]);

    // Inside range
    Task::withoutTenantScope()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->addDay(),
    ]);

    // Outside range (too late)
    Task::withoutTenantScope()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->addDays(20),
    ]);

    $service = app(TaskService::class);
    $result = $service->list([
        'date_from' => now()->startOfDay()->toDateTimeString(),
        'date_to' => now()->addDays(7)->toDateTimeString(),
    ]);

    expect($result->total())->toBe(1);
});

it('is_schedule filter returns only schedule-type tasks', function (): void {
    $school = svcMakeSchool();
    app()->instance('tenant.school_id', $school->id);

    $opportunity = svcMakeOpportunity($school);

    // Non-schedule task
    Task::withoutTenantScope()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->addHour(),
    ]);

    // Schedule task
    Task::withoutTenantScope()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::Agendamento->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->addHour(),
    ]);

    // Another schedule task
    Task::withoutTenantScope()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::LembreteAgenda->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->addHour(),
    ]);

    $service = app(TaskService::class);
    $result = $service->list(['is_schedule' => true]);

    expect($result->total())->toBe(2);
    foreach ($result->items() as $task) {
        expect($task->type->isSchedule())->toBeTrue();
    }
});
