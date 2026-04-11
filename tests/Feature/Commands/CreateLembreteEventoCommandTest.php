<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Event;
use App\Models\Grade;
use App\Models\Opportunity;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\Segment;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('tenant.school_id');
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function eventoCmdMakeSchool(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) ($counter + 95000), 14, '0', STR_PAD_LEFT),
        'legal_name' => "Escola EventoCmd {$counter}",
        'slug' => "escola-evento-cmd-{$counter}",
    ]);
}

function eventoCmdMakeOpportunity(School $school): Opportunity
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

function eventoCmdMakeEvent(School $school, mixed $eventDate): Event
{
    app()->instance('tenant.school_id', $school->id);

    $event = Event::withoutTenantScope()->create([
        'uuid' => Str::uuid()->toString(),
        'title' => 'Evento Teste',
        'has_no_date' => false,
        'event_date' => $eventDate,
    ]);

    app()->forgetInstance('tenant.school_id');

    return $event;
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

it('creates lembrete when event_date is within 24h', function (): void {
    $school = eventoCmdMakeSchool();
    $opportunity = eventoCmdMakeOpportunity($school);
    $event = eventoCmdMakeEvent($school, now()->addHours(12));
    $event->opportunities()->attach($opportunity->id);

    $this->artisan('tasks:create-lembrete-evento')
        ->assertSuccessful();

    $this->assertDatabaseHas('tasks', [
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::LembreteEvento->value,
        'status' => TaskStatus::Open->value,
    ]);
});

it('does NOT create when any open task exists', function (): void {
    $school = eventoCmdMakeSchool();
    $opportunity = eventoCmdMakeOpportunity($school);
    $event = eventoCmdMakeEvent($school, now()->addHours(12));
    $event->opportunities()->attach($opportunity->id);

    // A different open task already exists on the same opportunity
    Task::withoutGlobalScopes()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->addHours(12),
    ]);

    $this->artisan('tasks:create-lembrete-evento')
        ->assertSuccessful();

    $this->assertDatabaseMissing('tasks', [
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::LembreteEvento->value,
    ]);
});

it('does NOT create when event_date is more than 24h away', function (): void {
    $school = eventoCmdMakeSchool();
    $opportunity = eventoCmdMakeOpportunity($school);
    $event = eventoCmdMakeEvent($school, now()->addHours(48));
    $event->opportunities()->attach($opportunity->id);

    $this->artisan('tasks:create-lembrete-evento')
        ->assertSuccessful();

    $this->assertDatabaseMissing('tasks', [
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::LembreteEvento->value,
    ]);
});

it('does NOT create when opportunity is not linked to event', function (): void {
    $school = eventoCmdMakeSchool();
    $opportunity = eventoCmdMakeOpportunity($school);
    // Event within 24h but not linked to this opportunity
    eventoCmdMakeEvent($school, now()->addHours(12));

    $this->artisan('tasks:create-lembrete-evento')
        ->assertSuccessful();

    $this->assertDatabaseMissing('tasks', [
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::LembreteEvento->value,
    ]);
});
