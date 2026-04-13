<?php

declare(strict_types=1);

use App\Enums\OpportunityStatus;
use App\Models\Grade;
use App\Models\LeadSource;
use App\Models\Opportunity;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\Segment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
        'slug' => 'escola-oportunidade-'.$counter,
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
        ->postJson(route('tenant.opportunities.store'), [
            'grade_id' => $grade->uuid,
            'school_year_id' => $schoolYear->uuid,
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
        ->postJson(route('tenant.opportunities.store'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['grade_id', 'school_year_id']);
});

it('POST store com UUID inválido para grade_id retorna 422 com mensagem em português', function (): void {
    $user = opportunityMasterUser();
    $school = makeSchoolForOpportunityTests();
    $schoolYear = makeSchoolYearForOpportunity($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->postJson(route('tenant.opportunities.store'), [
            'grade_id' => (string) Str::uuid(),
            'school_year_id' => $schoolYear->uuid,
            'student_name' => 'Aluno Teste',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['grade_id']);
});

it('POST store com UUID inválido para segment_id retorna 422 com mensagem em português', function (): void {
    $user = opportunityMasterUser();
    $school = makeSchoolForOpportunityTests();
    $grade = makeGradeForOpportunity($school);
    $schoolYear = makeSchoolYearForOpportunity($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->postJson(route('tenant.opportunities.store'), [
            'grade_id' => $grade->uuid,
            'school_year_id' => $schoolYear->uuid,
            'student_name' => 'Aluno Teste',
            'segment_id' => (string) Str::uuid(),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['segment_id']);
});

it('POST store com UUID inválido para school_year_id retorna 422 com mensagem em português', function (): void {
    $user = opportunityMasterUser();
    $school = makeSchoolForOpportunityTests();
    $grade = makeGradeForOpportunity($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->postJson(route('tenant.opportunities.store'), [
            'grade_id' => $grade->uuid,
            'school_year_id' => (string) Str::uuid(),
            'student_name' => 'Aluno Teste',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['school_year_id']);
});

it('PUT update atualiza a Oportunidade e redireciona', function (): void {
    $user = opportunityMasterUser();
    $school = makeSchoolForOpportunityTests();
    $opportunity = makeOpportunity($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->putJson(route('tenant.opportunities.update', [$opportunity]), [
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
        ->putJson(route('tenant.opportunities.update', [$opportunity]), [
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
        ->deleteJson(route('tenant.opportunities.destroy', [$opportunity]))
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
        ->deleteJson(route('tenant.opportunities.destroy', [$opportunity]))
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
        ->postJson(route('tenant.opportunities.store'), [
            'grade_id' => $grade->uuid,
            'school_year_id' => $schoolYear->uuid,
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
        ->postJson(route('tenant.opportunities.store'), [
            'grade_id' => $grade->uuid,
            'school_year_id' => $schoolYear->uuid,
            'student_name' => 'Aluno Complementar',
            'registration_type' => 'agendamento',
            'segment_id' => $segment->uuid,
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

it('GET index sem view retorna kanban_columns com todos os status e opportunities null', function (): void {
    $user = opportunityMasterUser();
    $school = makeSchoolForOpportunityTests();

    app()->instance('tenant.school_id', $school->id);

    $response = $this->withoutVite()
        ->actingAs($user)
        ->get(route('tenant.opportunities.index'));

    $response->assertStatus(200);
    $response->assertInertia(function ($page): void {
        $page->where('view', 'kanban')
            ->where('opportunities', null)
            ->has('kanban_columns');

        foreach (OpportunityStatus::cases() as $case) {
            $page->has('kanban_columns.'.$case->value);
        }
    });
});

it('GET index com view=list retorna opportunities paginado e kanban_columns null', function (): void {
    $user = opportunityMasterUser();
    $school = makeSchoolForOpportunityTests();

    app()->instance('tenant.school_id', $school->id);

    $response = $this->withoutVite()
        ->actingAs($user)
        ->get(route('tenant.opportunities.index', ['view' => 'list']));

    $response->assertStatus(200);
    $response->assertInertia(function ($page): void {
        $page->where('view', 'list')
            ->where('kanban_columns', null)
            ->has('opportunities');
    });
});

it('GET index com lead_source_id filtra corretamente em view list', function (): void {
    $user = opportunityMasterUser();
    $school = makeSchoolForOpportunityTests();

    $leadSource = LeadSource::create([
        'name' => 'Lead Test Filter',
        'school_id' => $school->id,
    ]);

    $grade = makeGradeForOpportunity($school);
    $schoolYear = makeSchoolYearForOpportunity($school);

    Opportunity::withoutTenantScope()->create([
        'school_id' => $school->id,
        'grade_id' => $grade->id,
        'school_year_id' => $schoolYear->id,
        'lead_source_id' => $leadSource->id,
        'status' => 'cadastro_inicial',
    ]);

    // Create another opportunity with no lead source
    Opportunity::withoutTenantScope()->create([
        'school_id' => $school->id,
        'grade_id' => $grade->id,
        'school_year_id' => $schoolYear->id,
        'lead_source_id' => null,
        'status' => 'cadastro_inicial',
    ]);

    app()->instance('tenant.school_id', $school->id);

    $response = $this->withoutVite()
        ->actingAs($user)
        ->get(route('tenant.opportunities.index', [
            'view' => 'list',
            'lead_source_id' => $leadSource->uuid,
        ]));

    $response->assertStatus(200);
    $response->assertInertia(function ($page): void {
        $page->where('view', 'list');
        // The filtered result should have exactly 1 item
        $page->where('opportunities.meta.total', 1);
    });
});

it('GET index com date_from e date_to filtra por intervalo de datas', function (): void {
    $user = opportunityMasterUser();
    $school = makeSchoolForOpportunityTests();
    $grade = makeGradeForOpportunity($school);
    $schoolYear = makeSchoolYearForOpportunity($school);

    // Use DB::table to bypass observer and set specific timestamps
    DB::table('opportunities')->insert([
        'uuid' => (string) Str::uuid(),
        'school_id' => $school->id,
        'grade_id' => $grade->id,
        'school_year_id' => $schoolYear->id,
        'status' => 'cadastro_inicial',
        'created_at' => '2026-06-15 10:00:00',
        'updated_at' => '2026-06-15 10:00:00',
    ]);

    // Opportunity outside date range
    DB::table('opportunities')->insert([
        'uuid' => (string) Str::uuid(),
        'school_id' => $school->id,
        'grade_id' => $grade->id,
        'school_year_id' => $schoolYear->id,
        'status' => 'cadastro_inicial',
        'created_at' => '2025-01-01 10:00:00',
        'updated_at' => '2025-01-01 10:00:00',
    ]);

    app()->instance('tenant.school_id', $school->id);

    $response = $this->withoutVite()
        ->actingAs($user)
        ->get(route('tenant.opportunities.index', [
            'view' => 'list',
            'date_from' => '2026-01-01',
            'date_to' => '2026-12-31',
        ]));

    $response->assertStatus(200);
    $response->assertInertia(function ($page): void {
        $page->where('view', 'list')
            ->where('opportunities.meta.total', 1);
    });
});

it('POST store bloqueia duplicata com mesmo aluno e mesmo ano letivo no mesmo tenant', function (): void {
    $user = opportunityMasterUser();
    $school = makeSchoolForOpportunityTests();
    $grade = makeGradeForOpportunity($school);
    $schoolYear = makeSchoolYearForOpportunity($school);

    // Create a student with CPF in this tenant
    $student = Student::withoutTenantScope()->create([
        'school_id' => $school->id,
        'name' => 'Aluno Duplicata',
        'cpf' => '529.982.247-25',
    ]);

    // Create an existing opportunity linked to that student
    Opportunity::withoutTenantScope()->create([
        'school_id' => $school->id,
        'grade_id' => $grade->id,
        'school_year_id' => $schoolYear->id,
        'student_id' => $student->id,
        'status' => 'cadastro_inicial',
    ]);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->postJson(route('tenant.opportunities.store'), [
            'grade_id' => $grade->uuid,
            'school_year_id' => $schoolYear->uuid,
            'student_name' => 'Aluno Duplicata',
            'student_cpf' => '529.982.247-25',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['student_cpf']);
});

it('POST store permite mesmo aluno com ano letivo diferente', function (): void {
    $user = opportunityMasterUser();
    $school = makeSchoolForOpportunityTests();
    $grade = makeGradeForOpportunity($school);
    $schoolYear = makeSchoolYearForOpportunity($school);

    $differentSchoolYear = SchoolYear::withoutTenantScope()->create([
        'school_id' => $school->id,
        'name' => '2026',
        'start' => '2026-01-01',
        'end' => '2026-12-31',
        'status' => 'planejamento',
    ]);

    // Create a student with CPF
    $student = Student::withoutTenantScope()->create([
        'school_id' => $school->id,
        'name' => 'Aluno Ano Diferente',
        'cpf' => '153.509.460-56',
    ]);

    // Existing opportunity for school_year (not differentSchoolYear)
    Opportunity::withoutTenantScope()->create([
        'school_id' => $school->id,
        'grade_id' => $grade->id,
        'school_year_id' => $schoolYear->id,
        'student_id' => $student->id,
        'status' => 'cadastro_inicial',
    ]);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->postJson(route('tenant.opportunities.store'), [
            'grade_id' => $grade->uuid,
            'school_year_id' => $differentSchoolYear->uuid,
            'student_name' => 'Aluno Ano Diferente',
            'student_cpf' => '153.509.460-56',
        ])
        ->assertRedirect(route('tenant.opportunities.index'));
});

it('POST store permite mesmo aluno e mesmo ano letivo em tenant diferente', function (): void {
    $userA = opportunityMasterUser();
    $schoolA = makeSchoolForOpportunityTests();
    $gradeA = makeGradeForOpportunity($schoolA);
    $schoolYearA = makeSchoolYearForOpportunity($schoolA);

    $schoolB = makeSchoolForOpportunityTests();
    $gradeB = makeGradeForOpportunity($schoolB);
    $schoolYearB = SchoolYear::withoutTenantScope()->create([
        'school_id' => $schoolB->id,
        'name' => '2025',
        'start' => '2025-01-01',
        'end' => '2025-12-31',
        'status' => 'planejamento',
    ]);

    // Student in tenant A with CPF
    $studentA = Student::withoutTenantScope()->create([
        'school_id' => $schoolA->id,
        'name' => 'Aluno Cross Tenant',
        'cpf' => '919.582.782-03',
    ]);

    // Existing opportunity in tenant A
    Opportunity::withoutTenantScope()->create([
        'school_id' => $schoolA->id,
        'grade_id' => $gradeA->id,
        'school_year_id' => $schoolYearA->id,
        'student_id' => $studentA->id,
        'status' => 'cadastro_inicial',
    ]);

    $userB = opportunityMasterUser();
    $schoolB->users()->attach($userB->id, ['is_active' => true]);

    app()->instance('tenant.school_id', $schoolB->id);

    $this->actingAs($userB)
        ->postJson(route('tenant.opportunities.store'), [
            'grade_id' => $gradeB->uuid,
            'school_year_id' => $schoolYearB->uuid,
            'student_name' => 'Aluno Cross Tenant',
            'student_cpf' => '919.582.782-03',
        ])
        ->assertRedirect(route('tenant.opportunities.index'));
});

it('POST store permite cadastro quando CPF do aluno não existe na tabela de alunos', function (): void {
    $user = opportunityMasterUser();
    $school = makeSchoolForOpportunityTests();
    $grade = makeGradeForOpportunity($school);
    $schoolYear = makeSchoolYearForOpportunity($school);

    app()->instance('tenant.school_id', $school->id);

    // CPF does not exist in students table
    $this->actingAs($user)
        ->postJson(route('tenant.opportunities.store'), [
            'grade_id' => $grade->uuid,
            'school_year_id' => $schoolYear->uuid,
            'student_name' => 'Novo Aluno',
            'student_cpf' => '036.273.750-92',
        ])
        ->assertRedirect(route('tenant.opportunities.index'));
});
