<?php

declare(strict_types=1);

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

function agendaCmdMakeSchool(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) ($counter + 90000), 14, '0', STR_PAD_LEFT),
        'legal_name' => "Escola AgendaCmd {$counter}",
        'slug' => "escola-agenda-cmd-{$counter}",
    ]);
}

function agendaCmdMakeOpportunity(School $school): Opportunity
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
        'status' => 'cadastro_inicial',
        'renitente_count' => 0,
    ]);
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

it('creates lembrete when agendamento due_at is within 24h', function (): void {
    $school = agendaCmdMakeSchool();
    $opportunity = agendaCmdMakeOpportunity($school);

    Task::withoutGlobalScopes()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::Agendamento->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->addHours(12),
    ]);

    $this->artisan('tasks:create-lembrete-agenda')
        ->assertSuccessful();

    $this->assertDatabaseHas('tasks', [
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::LembreteAgenda->value,
        'status' => TaskStatus::Open->value,
    ]);
});

it('does NOT create when any open task exists', function (): void {
    $school = agendaCmdMakeSchool();
    $opportunity = agendaCmdMakeOpportunity($school);

    Task::withoutGlobalScopes()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::Agendamento->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->addHours(12),
    ]);

    // A different open task already exists on the same opportunity
    Task::withoutGlobalScopes()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->addHours(12),
    ]);

    $this->artisan('tasks:create-lembrete-agenda')
        ->assertSuccessful();

    $this->assertDatabaseMissing('tasks', [
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::LembreteAgenda->value,
    ]);
});

it('does NOT create when agendamento due_at is more than 24h away', function (): void {
    $school = agendaCmdMakeSchool();
    $opportunity = agendaCmdMakeOpportunity($school);

    Task::withoutGlobalScopes()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::Agendamento->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->addHours(48),
    ]);

    $this->artisan('tasks:create-lembrete-agenda')
        ->assertSuccessful();

    $this->assertDatabaseMissing('tasks', [
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::LembreteAgenda->value,
    ]);
});

it('does NOT create when agendamento status is not open', function (): void {
    $school = agendaCmdMakeSchool();
    $opportunity = agendaCmdMakeOpportunity($school);

    Task::withoutGlobalScopes()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::Agendamento->value,
        'status' => TaskStatus::Completed->value,
        'due_at' => now()->addHours(12),
    ]);

    $this->artisan('tasks:create-lembrete-agenda')
        ->assertSuccessful();

    $this->assertDatabaseMissing('tasks', [
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::LembreteAgenda->value,
    ]);
});

it('does NOT create when due_at is in the past', function (): void {
    $school = agendaCmdMakeSchool();
    $opportunity = agendaCmdMakeOpportunity($school);

    Task::withoutGlobalScopes()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::Agendamento->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->subHours(2),
    ]);

    $this->artisan('tasks:create-lembrete-agenda')
        ->assertSuccessful();

    $this->assertDatabaseMissing('tasks', [
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::LembreteAgenda->value,
    ]);
});
