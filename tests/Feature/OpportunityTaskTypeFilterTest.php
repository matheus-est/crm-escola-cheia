<?php

declare(strict_types=1);

use App\Models\Grade;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\Segment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('tenant.school_id');
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function ottMakeSchool(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) ($counter + 90000), 14, '0', STR_PAD_LEFT),
        'legal_name' => "Escola OTT {$counter}",
        'slug' => "escola-ott-{$counter}",
    ]);
}

function ottMakeUser(School $school): User
{
    $role = Role::firstOrCreate(
        ['name' => 'Gestor'],
        ['description' => 'Gestor', 'is_default' => false],
    );

    $user = User::factory()->create();
    $user->role()->associate($role)->save();
    $school->users()->attach($user->id, ['is_active' => true]);

    return $user;
}

function ottMakeGrade(School $school): Grade
{
    $segment = Segment::firstOrCreate(
        ['name' => 'Ensino Fundamental'],
        ['name' => 'Ensino Fundamental'],
    );

    return Grade::withoutTenantScope()->firstOrCreate([
        'school_id' => $school->id,
        'segment_id' => $segment->id,
        'name' => '1º Ano OTT',
    ]);
}

function ottMakeSchoolYear(School $school): SchoolYear
{
    return SchoolYear::withoutTenantScope()->firstOrCreate(
        ['school_id' => $school->id, 'name' => '2025-OTT'],
        [
            'start' => '2025-01-01',
            'end' => '2025-12-31',
            'status' => 'planejamento',
        ]
    );
}

// ---------------------------------------------------------------------------
// Test 1: registration_type=agendamento + task_type=agendamento → passes
// ---------------------------------------------------------------------------

it('registration_type agendamento with task_type agendamento passes validation', function (): void {
    $school = ottMakeSchool();
    $user = ottMakeUser($school);
    $grade = ottMakeGrade($school);
    $schoolYear = ottMakeSchoolYear($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->postJson(route('tenant.opportunities.store'), [
            'grade_id' => $grade->uuid,
            'school_year_id' => $schoolYear->uuid,
            'student_name' => 'Aluno Agendamento',
            'registration_type' => 'agendamento',
            'task_type' => 'agendamento',
        ])
        ->assertRedirect(route('tenant.opportunities.index'));
});

// ---------------------------------------------------------------------------
// Test 2: registration_type=agendamento + task_type=lembrete_evento → 422
// ---------------------------------------------------------------------------

it('registration_type agendamento with task_type lembrete_evento returns 422 on task_type', function (): void {
    $school = ottMakeSchool();
    $user = ottMakeUser($school);
    $grade = ottMakeGrade($school);
    $schoolYear = ottMakeSchoolYear($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->postJson(route('tenant.opportunities.store'), [
            'grade_id' => $grade->uuid,
            'school_year_id' => $schoolYear->uuid,
            'student_name' => 'Aluno Agendamento Errado',
            'registration_type' => 'agendamento',
            'task_type' => 'lembrete_evento',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['task_type']);
});

// ---------------------------------------------------------------------------
// Test 3: registration_type=evento + valid evento task_type → passes
// ---------------------------------------------------------------------------

it('registration_type evento with evento task_type passes validation', function (): void {
    $school = ottMakeSchool();
    $user = ottMakeUser($school);
    $grade = ottMakeGrade($school);
    $schoolYear = ottMakeSchoolYear($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->postJson(route('tenant.opportunities.store'), [
            'grade_id' => $grade->uuid,
            'school_year_id' => $schoolYear->uuid,
            'student_name' => 'Aluno Evento',
            'registration_type' => 'evento',
            'task_type' => 'lembrete_evento',
        ])
        ->assertRedirect(route('tenant.opportunities.index'));
});
