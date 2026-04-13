<?php

declare(strict_types=1);

use App\Models\Grade;
use App\Models\Guardian;
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

function addressMasterUser(): User
{
    $role = Role::firstOrCreate(
        ['name' => 'Master'],
        ['description' => 'Master', 'is_default' => false],
    );

    $user = User::factory()->create();
    $user->role()->associate($role)->save();

    return $user;
}

function makeSchoolForAddressTests(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) ($counter + 6000), 14, '0', STR_PAD_LEFT),
        'legal_name' => 'Escola Address '.$counter,
        'slug' => 'escola-address-'.$counter,
    ]);
}

function makeGradeForAddressTests(School $school): Grade
{
    $segment = Segment::firstOrCreate(['name' => 'Ensino Fundamental']);

    return Grade::withoutTenantScope()->create([
        'school_id' => $school->id,
        'segment_id' => $segment->id,
        'name' => '3º Ano',
    ]);
}

function makeSchoolYearForAddressTests(School $school): SchoolYear
{
    return SchoolYear::withoutTenantScope()->create([
        'school_id' => $school->id,
        'name' => '2025',
        'start' => '2025-01-01',
        'end' => '2025-12-31',
        'status' => 'planejamento',
    ]);
}

it('POST store persiste campos de endereço do guardian', function (): void {
    $user = addressMasterUser();
    $school = makeSchoolForAddressTests();
    $grade = makeGradeForAddressTests($school);
    $schoolYear = makeSchoolYearForAddressTests($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->postJson(route('tenant.opportunities.store'), [
            'grade_id' => $grade->uuid,
            'school_year_id' => $schoolYear->uuid,
            'student_name' => 'Aluno Endereço',
            'guardian_name' => 'Responsável Endereço',
            'guardian_cpf' => '529.982.247-25',
            'guardian_phone' => '(11) 98765-4321',
            'zip_code' => '01310-100',
            'street' => 'Av. Paulista',
            'number' => '1000',
            'neighborhood' => 'Bela Vista',
            'city' => 'São Paulo',
            'state' => 'SP',
        ])
        ->assertRedirect(route('tenant.opportunities.index'));

    $guardian = Guardian::withoutTenantScope()
        ->where('school_id', $school->id)
        ->where('name', 'Responsável Endereço')
        ->first();

    expect($guardian)->not->toBeNull()
        ->and($guardian->phone)->toBe('(11) 98765-4321')
        ->and($guardian->zip_code)->toBe('01310-100')
        ->and($guardian->street)->toBe('Av. Paulista')
        ->and($guardian->number)->toBe('1000')
        ->and($guardian->neighborhood)->toBe('Bela Vista')
        ->and($guardian->city)->toBe('São Paulo')
        ->and($guardian->state)->toBe('SP');
});

it('POST store com guardian_cpf inválido retorna 422', function (): void {
    $user = addressMasterUser();
    $school = makeSchoolForAddressTests();
    $grade = makeGradeForAddressTests($school);
    $schoolYear = makeSchoolYearForAddressTests($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->postJson(route('tenant.opportunities.store'), [
            'grade_id' => $grade->uuid,
            'school_year_id' => $schoolYear->uuid,
            'student_name' => 'Aluno CPF Inválido',
            'guardian_cpf' => '111.111.111-11',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['guardian_cpf']);
});

it('POST store com student_cpf inválido retorna 422', function (): void {
    $user = addressMasterUser();
    $school = makeSchoolForAddressTests();
    $grade = makeGradeForAddressTests($school);
    $schoolYear = makeSchoolYearForAddressTests($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->postJson(route('tenant.opportunities.store'), [
            'grade_id' => $grade->uuid,
            'school_year_id' => $schoolYear->uuid,
            'student_name' => 'Aluno CPF Inválido',
            'student_cpf' => '000.000.000-00',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['student_cpf']);
});
