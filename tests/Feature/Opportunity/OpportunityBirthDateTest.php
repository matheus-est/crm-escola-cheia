<?php

declare(strict_types=1);

use App\Models\Grade;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\Segment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('tenant.school_id');
});

function birthDateMasterUser(): User
{
    $role = Role::firstOrCreate(
        ['name' => 'Master'],
        ['description' => 'Master', 'is_default' => false],
    );

    $user = User::factory()->create();
    $user->role()->associate($role)->save();

    return $user;
}

function makeSchoolForBirthDateTests(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) ($counter + 7000), 14, '0', STR_PAD_LEFT),
        'legal_name' => 'Escola BirthDate '.$counter,
        'slug' => 'escola-birthdate-'.$counter,
    ]);
}

function makeGradeForBirthDateTests(School $school): Grade
{
    $segment = Segment::firstOrCreate(['name' => 'Ensino Fundamental']);

    return Grade::withoutTenantScope()->create([
        'school_id' => $school->id,
        'segment_id' => $segment->id,
        'name' => '1º Ano',
    ]);
}

function makeSchoolYearForBirthDateTests(School $school): SchoolYear
{
    return SchoolYear::withoutTenantScope()->create([
        'school_id' => $school->id,
        'name' => '2025',
        'start' => '2025-01-01',
        'end' => '2025-12-31',
        'status' => 'planejamento',
    ]);
}

it('criação de oportunidade com student_birth_date persiste date_of_birth no aluno', function (): void {
    $user = birthDateMasterUser();
    $school = makeSchoolForBirthDateTests();
    $grade = makeGradeForBirthDateTests($school);
    $schoolYear = makeSchoolYearForBirthDateTests($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->postJson(route('tenant.opportunities.store'), [
            'grade_id' => $grade->uuid,
            'school_year_id' => $schoolYear->uuid,
            'student_name' => 'João da Silva',
            'student_cpf' => '529.982.247-25',
            'student_birth_date' => '2010-05-15',
            'guardian_name' => 'Maria da Silva',
            'guardian_cpf' => '295.311.220-08',
        ])
        ->assertRedirect(route('tenant.opportunities.index'));

    $student = Student::withoutTenantScope()
        ->where('school_id', $school->id)
        ->where('cpf', '529.982.247-25')
        ->first();

    expect($student)->not->toBeNull()
        ->and($student->date_of_birth->format('Y-m-d'))->toBe('2010-05-15');
});

it('criação de oportunidade com aluno CPF já existente atualiza date_of_birth', function (): void {
    $user = birthDateMasterUser();
    $school = makeSchoolForBirthDateTests();
    $grade = makeGradeForBirthDateTests($school);
    $schoolYear = makeSchoolYearForBirthDateTests($school);

    app()->instance('tenant.school_id', $school->id);

    // Create an existing student without birth date
    $existingStudent = Student::withoutTenantScope()->create([
        'school_id' => $school->id,
        'name' => 'Pedro Souza',
        'cpf' => '295.311.220-08',
        'date_of_birth' => null,
    ]);

    $this->actingAs($user)
        ->postJson(route('tenant.opportunities.store'), [
            'grade_id' => $grade->uuid,
            'school_year_id' => $schoolYear->uuid,
            'student_name' => 'Pedro Souza',
            'student_cpf' => '295.311.220-08',
            'student_birth_date' => '2008-11-22',
            'guardian_name' => 'Ana Souza',
            'guardian_cpf' => '529.982.247-25',
        ])
        ->assertRedirect(route('tenant.opportunities.index'));

    $existingStudent->refresh();

    expect($existingStudent->date_of_birth)->not->toBeNull()
        ->and($existingStudent->date_of_birth->format('Y-m-d'))->toBe('2008-11-22');
});

it('criação de oportunidade sem student_birth_date não falha', function (): void {
    $user = birthDateMasterUser();
    $school = makeSchoolForBirthDateTests();
    $grade = makeGradeForBirthDateTests($school);
    $schoolYear = makeSchoolYearForBirthDateTests($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->postJson(route('tenant.opportunities.store'), [
            'grade_id' => $grade->uuid,
            'school_year_id' => $schoolYear->uuid,
            'student_name' => 'Lucas Pereira',
            'guardian_cpf' => '529.982.247-25',
        ])
        ->assertRedirect(route('tenant.opportunities.index'));

    $student = Student::withoutTenantScope()
        ->where('school_id', $school->id)
        ->where('name', 'Lucas Pereira')
        ->first();

    expect($student)->not->toBeNull()
        ->and($student->date_of_birth)->toBeNull();
});
