<?php

declare(strict_types=1);

use App\Enums\OpportunityStatus;
use App\Models\Grade;
use App\Models\Opportunity;
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

function opportunityMasterUser(): User
{
    $role = Role::firstOrCreate(
        ['name' => 'Master'],
        ['description' => 'Master', 'is_default' => false],
    );

    $user = User::factory()->create();
    $user->role()->associate($role)->save();

    return $user;
}

function opportunityGestorUser(School $school): User
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

function makeSchoolForOpportunityTests(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) ($counter + 8000), 14, '0', STR_PAD_LEFT),
        'legal_name' => 'Escola Oportunidade '.$counter,
    ]);
}

function makeGradeForOpportunity(School $school): Grade
{
    $segment = Segment::firstOrCreate(
        ['name' => 'Ensino Fundamental'],
        ['name' => 'Ensino Fundamental'],
    );

    return Grade::withoutTenantScope()->create([
        'school_id' => $school->id,
        'segment_id' => $segment->id,
        'name' => '1º Ano',
    ]);
}

function makeSchoolYearForOpportunity(School $school): SchoolYear
{
    return SchoolYear::withoutTenantScope()->create([
        'school_id' => $school->id,
        'name' => '2025',
        'start' => '2025-01-01',
        'end' => '2025-12-31',
        'status' => 'planejamento',
    ]);
}

function makeOpportunity(School $school, string $status = 'cadastro_inicial'): Opportunity
{
    $grade = makeGradeForOpportunity($school);
    $schoolYear = makeSchoolYearForOpportunity($school);

    return Opportunity::withoutTenantScope()->create([
        'school_id' => $school->id,
        'grade_id' => $grade->id,
        'school_year_id' => $schoolYear->id,
        'status' => $status,
    ]);
}

it('GET index retorna 200 para usuário Master autenticado', function (): void {
    $user = opportunityMasterUser();
    $school = makeSchoolForOpportunityTests();

    app()->instance('tenant.school_id', $school->id);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('tenant.opportunities.index'))
        ->assertStatus(200);
});

it('GET index retorna 302 para usuário não autenticado', function (): void {
    $school = makeSchoolForOpportunityTests();

    $this->get(route('tenant.opportunities.index'))
        ->assertStatus(302);
});

it('GET create retorna 200 para usuário Master autenticado', function (): void {
    $user = opportunityMasterUser();
    $school = makeSchoolForOpportunityTests();

    app()->instance('tenant.school_id', $school->id);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('tenant.opportunities.create'))
        ->assertStatus(200);
});

it('POST store cria uma Oportunidade e redireciona', function (): void {
    $user = opportunityMasterUser();
    $school = makeSchoolForOpportunityTests();
    $grade = makeGradeForOpportunity($school);
    $schoolYear = makeSchoolYearForOpportunity($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->post(route('tenant.opportunities.store'), [
            'grade_id' => $grade->id,
            'school_year_id' => $schoolYear->id,
            'student_name' => 'Aluno Teste',
            'observations' => 'Observação de teste.',
        ])
        ->assertRedirect(route('tenant.opportunities.index'));

    $this->assertDatabaseHas('opportunities', [
        'school_id' => $school->id,
        'grade_id' => $grade->id,
        'school_year_id' => $schoolYear->id,
    ]);
});

it('POST store com grade_id e school_year_id ausentes retorna 422', function (): void {
    $user = opportunityMasterUser();
    $school = makeSchoolForOpportunityTests();

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('tenant.opportunities.store'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['grade_id', 'school_year_id']);
});

it('PUT update atualiza a Oportunidade e redireciona', function (): void {
    $user = opportunityMasterUser();
    $school = makeSchoolForOpportunityTests();
    $opportunity = makeOpportunity($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->put(route('tenant.opportunities.update', [$opportunity]), [
            'observations' => 'Observação atualizada.',
        ])
        ->assertRedirect(route('tenant.opportunities.index'));

    $this->assertDatabaseHas('opportunities', [
        'id' => $opportunity->id,
        'observations' => 'Observação atualizada.',
    ]);
});

it('PUT update em oportunidade com status terminal retorna 422', function (): void {
    $user = opportunityMasterUser();
    $school = makeSchoolForOpportunityTests();
    $opportunity = makeOpportunity($school, OpportunityStatus::Matricula->value);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->put(route('tenant.opportunities.update', [$opportunity]), [
            'observations' => 'Tentando editar.',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

it('DELETE destroy soft-deleta a Oportunidade e redireciona', function (): void {
    $user = opportunityMasterUser();
    $school = makeSchoolForOpportunityTests();
    $opportunity = makeOpportunity($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->delete(route('tenant.opportunities.destroy', [$opportunity]))
        ->assertRedirect(route('tenant.opportunities.index'));

    $this->assertSoftDeleted('opportunities', [
        'id' => $opportunity->id,
    ]);
});

it('DELETE destroy por usuário Gestor retorna 403', function (): void {
    $school = makeSchoolForOpportunityTests();
    $gestor = opportunityGestorUser($school);
    $opportunity = makeOpportunity($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($gestor)
        ->delete(route('tenant.opportunities.destroy', [$opportunity]))
        ->assertStatus(403);
});

it('Oportunidade de outra escola não é acessível pelo usuário do tenant B', function (): void {
    $schoolA = makeSchoolForOpportunityTests();
    $schoolB = makeSchoolForOpportunityTests();

    $user = opportunityMasterUser();
    $schoolB->users()->attach($user->id, ['is_active' => true]);

    $opportunityA = makeOpportunity($schoolA);

    app()->instance('tenant.school_id', $schoolB->id);

    $this->actingAs($user)
        ->get(route('tenant.opportunities.edit', [$opportunityA->uuid]))
        ->assertStatus(404);
});

it('POST store com school_year encerrado cria oportunidade e redireciona com warning', function (): void {
    $user = opportunityMasterUser();
    $school = makeSchoolForOpportunityTests();
    $grade = makeGradeForOpportunity($school);

    $schoolYear = SchoolYear::withoutTenantScope()->create([
        'school_id' => $school->id,
        'name' => '2023',
        'start' => '2023-01-01',
        'end' => '2023-12-31',
        'status' => 'encerrado',
    ]);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->post(route('tenant.opportunities.store'), [
            'grade_id' => $grade->id,
            'school_year_id' => $schoolYear->id,
            'student_name' => 'Aluno Ano Encerrado',
        ])
        ->assertRedirect(route('tenant.opportunities.index'))
        ->assertSessionHas('warning');

    $this->assertDatabaseHas('opportunities', [
        'school_id' => $school->id,
        'school_year_id' => $schoolYear->id,
    ]);
});

it('POST store com registration_type, segment_id, history e indications persiste os campos', function (): void {
    $user = opportunityMasterUser();
    $school = makeSchoolForOpportunityTests();
    $grade = makeGradeForOpportunity($school);
    $schoolYear = makeSchoolYearForOpportunity($school);

    $segment = Segment::firstOrCreate(
        ['name' => 'Ensino Fundamental'],
        ['name' => 'Ensino Fundamental'],
    );

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->post(route('tenant.opportunities.store'), [
            'grade_id' => $grade->id,
            'school_year_id' => $schoolYear->id,
            'student_name' => 'Aluno Complementar',
            'registration_type' => 'agendamento',
            'segment_id' => $segment->id,
            'history' => 'Histórico do aluno.',
            'indications' => 'Indicação de amigo.',
        ])
        ->assertStatus(302);

    $this->assertDatabaseHas('opportunities', [
        'school_id' => $school->id,
        'registration_type' => 'agendamento',
        'segment_id' => $segment->id,
        'history' => 'Histórico do aluno.',
        'indications' => 'Indicação de amigo.',
    ]);
});
