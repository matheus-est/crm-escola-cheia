<?php

declare(strict_types=1);

use App\Enums\OpportunityStatus;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Grade;
use App\Models\Opportunity;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\Segment;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('tenant.school_id');
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function observerMakeSchool(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) ($counter + 70000), 14, '0', STR_PAD_LEFT),
        'legal_name' => "Escola Observer {$counter}",
        'slug' => "escola-observer-{$counter}",
    ]);
}

function observerMakeOpportunity(School $school, string $status = 'cadastro_inicial'): Opportunity
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

function observerMakeOpenTask(Opportunity $opportunity): Task
{
    return Task::withoutTenantScope()->create([
        'school_id' => $opportunity->school_id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->addHour(),
    ]);
}

// ---------------------------------------------------------------------------
// Tests: terminal status cancels open tasks
// ---------------------------------------------------------------------------

it('changing status to matricula cancels all open tasks', function (): void {
    $school = observerMakeSchool();
    app()->instance('tenant.school_id', $school->id);

    $opportunity = observerMakeOpportunity($school, 'agendamento');
    $task1 = observerMakeOpenTask($opportunity);
    $task2 = observerMakeOpenTask($opportunity);

    // Remove open task guard temporarily by directly setting status
    Opportunity::withoutTenantScope()
        ->where('id', $opportunity->id)
        ->update(['status' => OpportunityStatus::Agendamento->value]);

    $opportunity->refresh();
    $opportunity->update(['status' => OpportunityStatus::Matricula->value]);

    expect(Task::withoutTenantScope()->find($task1->id)->status)->toBe(TaskStatus::Cancelled);
    expect(Task::withoutTenantScope()->find($task2->id)->status)->toBe(TaskStatus::Cancelled);
    expect(Task::withoutTenantScope()->find($task1->id)->cancelled_at)->not->toBeNull();
});

it('changing status to recusado cancels all open tasks', function (): void {
    $school = observerMakeSchool();
    app()->instance('tenant.school_id', $school->id);

    $opportunity = observerMakeOpportunity($school, 'cadastro_inicial');
    $task = observerMakeOpenTask($opportunity);

    $opportunity->update(['status' => OpportunityStatus::Recusado->value]);

    expect(Task::withoutTenantScope()->find($task->id)->status)->toBe(TaskStatus::Cancelled);
    expect(Task::withoutTenantScope()->find($task->id)->cancelled_at)->not->toBeNull();
});

it('non-terminal status change does NOT cancel tasks', function (): void {
    $school = observerMakeSchool();
    app()->instance('tenant.school_id', $school->id);

    $opportunity = observerMakeOpportunity($school, 'cadastro_inicial');
    $task = observerMakeOpenTask($opportunity);

    $opportunity->update(['status' => OpportunityStatus::Agendamento->value]);

    expect(Task::withoutTenantScope()->find($task->id)->status)->toBe(TaskStatus::Open);
});

it('already-cancelled tasks remain cancelled after terminal status change', function (): void {
    $school = observerMakeSchool();
    app()->instance('tenant.school_id', $school->id);

    $opportunity = observerMakeOpportunity($school, 'cadastro_inicial');

    $cancelledTask = Task::withoutTenantScope()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Cancelled->value,
        'cancelled_at' => now()->subDay(),
        'due_at' => now()->addHour(),
    ]);

    $originalCancelledAt = $cancelledTask->cancelled_at;

    $opportunity->update(['status' => OpportunityStatus::Recusado->value]);

    $cancelledTask->refresh();
    expect($cancelledTask->status)->toBe(TaskStatus::Cancelled);
});

// ---------------------------------------------------------------------------
// Tests: status_changed_at is set on status change
// ---------------------------------------------------------------------------

it('updated observer sets status_changed_at when status changes', function (): void {
    $school = observerMakeSchool();
    app()->instance('tenant.school_id', $school->id);

    $opportunity = observerMakeOpportunity($school, 'cadastro_inicial');

    expect($opportunity->fresh()->status_changed_at)->toBeNull();

    $opportunity->update(['status' => OpportunityStatus::Agendamento->value]);

    expect($opportunity->fresh()->status_changed_at)->not->toBeNull();
});

it('status_changed_at is NOT set when a non-status field changes', function (): void {
    $school = observerMakeSchool();
    app()->instance('tenant.school_id', $school->id);

    $opportunity = observerMakeOpportunity($school, 'cadastro_inicial');
    $opportunity->update(['observations' => 'alguma nota']);

    expect($opportunity->fresh()->status_changed_at)->toBeNull();
});
