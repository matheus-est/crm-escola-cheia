<?php

declare(strict_types=1);

use App\Models\Guardian;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('tenant.school_id');
});

function validateCpfMasterUser(): User
{
    $role = Role::firstOrCreate(
        ['name' => 'Master'],
        ['description' => 'Master', 'is_default' => false],
    );

    $user = User::factory()->create();
    $user->role()->associate($role)->save();

    return $user;
}

function makeSchoolForValidateCpfTests(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) ($counter + 7000), 14, '0', STR_PAD_LEFT),
        'razao_social' => 'Escola ValidateCpf '.$counter,
    ]);
}

it('retorna valid=false para CPF algoritmicamente inválido', function (): void {
    $user = validateCpfMasterUser();
    $school = makeSchoolForValidateCpfTests();

    app()->instance('tenant.school_id', $school->id);

    $response = $this->actingAs($user)
        ->getJson(route('tenant.guardians.validate_cpf', ['cpf' => '111.111.111-11']));

    $response->assertStatus(200)
        ->assertJson(['valid' => false, 'exists' => false]);
});

it('retorna valid=true e exists=false quando CPF válido e não cadastrado', function (): void {
    $user = validateCpfMasterUser();
    $school = makeSchoolForValidateCpfTests();

    app()->instance('tenant.school_id', $school->id);

    // 529.982.247-25 is a well-known valid CPF
    $response = $this->actingAs($user)
        ->getJson(route('tenant.guardians.validate_cpf', ['cpf' => '529.982.247-25']));

    $response->assertStatus(200)
        ->assertJson(['valid' => true, 'exists' => false]);
});

it('retorna valid=true e exists=true quando CPF válido e já cadastrado', function (): void {
    $user = validateCpfMasterUser();
    $school = makeSchoolForValidateCpfTests();

    app()->instance('tenant.school_id', $school->id);

    Guardian::withoutTenantScope()->create([
        'school_id' => $school->id,
        'nome' => 'Maria Responsável',
        'cpf' => '529.982.247-25',
        'telefone' => '(11) 91111-2222',
    ]);

    $response = $this->actingAs($user)
        ->getJson(route('tenant.guardians.validate_cpf', ['cpf' => '529.982.247-25']));

    $response->assertStatus(200)
        ->assertJson([
            'valid' => true,
            'exists' => true,
        ])
        ->assertJsonPath('guardian.nome', 'Maria Responsável')
        ->assertJsonPath('guardian.telefone', '(11) 91111-2222');
});

it('retorna 302 para usuário não autenticado', function (): void {
    $this->get(route('tenant.guardians.validate_cpf', ['cpf' => '529.982.247-25']))
        ->assertStatus(302);
});
