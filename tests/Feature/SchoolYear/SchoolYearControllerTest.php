<?php

declare(strict_types=1);

use App\Enums\SchoolYearStatus;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('tenant.school_id');
});

function schoolYearMasterUser(): User
{
    $role = Role::firstOrCreate(
        ['name' => 'Master'],
        ['description' => 'Master', 'is_default' => false],
    );

    $user = User::factory()->create();
    $user->role()->associate($role)->save();

    return $user;
}

function schoolYearGestorUser(School $school): User
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

function makeSchoolForYearTests(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) $counter, 14, '0', STR_PAD_LEFT),
        'razao_social' => 'Escola Ano Letivo '.$counter,
    ]);
}

function makeSchoolYear(School $school): SchoolYear
{
    return SchoolYear::withoutTenantScope()->create([
        'school_id' => $school->id,
        'nome' => '2025',
        'inicio' => '2025-01-01',
        'fim' => '2025-12-31',
        'status' => SchoolYearStatus::Planejamento,
    ]);
}

it('GET index retorna 200 para usuário Master autenticado', function (): void {
    $user = schoolYearMasterUser();
    $school = makeSchoolForYearTests();

    app()->instance('tenant.school_id', $school->id);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('tenant.school_years.index', $school))
        ->assertStatus(200);
});

it('GET index retorna 200 para usuário Gestor com acesso ao tenant', function (): void {
    $school = makeSchoolForYearTests();
    $user = schoolYearGestorUser($school);

    app()->instance('tenant.school_id', $school->id);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('tenant.school_years.index', $school))
        ->assertStatus(200);
});

it('GET index retorna 302 para usuário não autenticado', function (): void {
    $school = makeSchoolForYearTests();

    $this->get(route('tenant.school_years.index', $school))
        ->assertStatus(302);
});

it('POST store cria um SchoolYear e redireciona', function (): void {
    $user = schoolYearMasterUser();
    $school = makeSchoolForYearTests();

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->post(route('tenant.school_years.store', $school), [
            'nome' => 'Ano Letivo 2025',
            'inicio' => '2025-01-01',
            'fim' => '2025-12-31',
            'status' => 'planejamento',
        ])
        ->assertRedirect(route('tenant.school_years.index', $school));

    $this->assertDatabaseHas('school_years', [
        'school_id' => $school->id,
        'nome' => 'Ano Letivo 2025',
    ]);
});

it('PUT update atualiza o SchoolYear e redireciona', function (): void {
    $user = schoolYearMasterUser();
    $school = makeSchoolForYearTests();
    $schoolYear = makeSchoolYear($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->put(route('tenant.school_years.update', [$school, $schoolYear]), [
            'nome' => 'Ano Letivo 2025 Atualizado',
            'inicio' => '2025-01-01',
            'fim' => '2025-12-31',
            'status' => 'ativo',
        ])
        ->assertRedirect(route('tenant.school_years.index', $school));

    $this->assertDatabaseHas('school_years', [
        'id' => $schoolYear->id,
        'nome' => 'Ano Letivo 2025 Atualizado',
        'status' => 'ativo',
    ]);
});

it('DELETE destroy remove o SchoolYear e redireciona', function (): void {
    $user = schoolYearMasterUser();
    $school = makeSchoolForYearTests();
    $schoolYear = makeSchoolYear($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->delete(route('tenant.school_years.destroy', [$school, $schoolYear]))
        ->assertRedirect(route('tenant.school_years.index', $school));

    $this->assertDatabaseMissing('school_years', [
        'id' => $schoolYear->id,
    ]);
});

it('POST store retorna erro de validação quando fim é anterior a inicio', function (): void {
    $user = schoolYearMasterUser();
    $school = makeSchoolForYearTests();

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('tenant.school_years.store', $school), [
            'nome' => 'Ano Letivo Inválido',
            'inicio' => '2025-12-31',
            'fim' => '2025-01-01',
            'status' => 'planejamento',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['fim']);
});

it('Comercial recebe 403 ao tentar criar SchoolYear', function (): void {
    $role = Role::firstOrCreate(
        ['name' => 'Comercial'],
        ['description' => 'Comercial', 'is_default' => false],
    );

    $school = makeSchoolForYearTests();
    $user = User::factory()->create();
    $user->role()->associate($role)->save();
    $school->users()->attach($user->id, ['is_active' => true]);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('tenant.school_years.store', $school), [
            'nome' => 'Ano Letivo 2025',
            'inicio' => '2025-01-01',
            'fim' => '2025-12-31',
            'status' => 'planejamento',
        ])
        ->assertStatus(403);
});
