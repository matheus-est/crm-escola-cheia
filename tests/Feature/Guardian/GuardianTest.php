<?php

declare(strict_types=1);

use App\Models\Guardian;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\Guardian\GuardianService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('tenant.school_id');
});

function guardianMasterUser(): User
{
    $role = Role::firstOrCreate(
        ['name' => 'Master'],
        ['description' => 'Master', 'is_default' => false],
    );

    $user = User::factory()->create();
    $user->role()->associate($role)->save();

    return $user;
}

function guardianGestorUser(School $school): User
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

function makeSchoolForGuardianTests(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) ($counter + 9000), 14, '0', STR_PAD_LEFT),
        'legal_name' => 'Escola Guardian '.$counter,
    ]);
}

function makeGuardian(School $school, string $cpf = '987.654.321-00'): Guardian
{
    return Guardian::withoutTenantScope()->create([
        'school_id' => $school->id,
        'name' => 'Responsável Teste',
        'cpf' => $cpf,
        'phone' => '(11) 99999-9999',
        'email' => 'responsavel@teste.com',
    ]);
}

it('GET index retorna 200 para usuário Master autenticado', function (): void {
    $user = guardianMasterUser();
    $school = makeSchoolForGuardianTests();

    app()->instance('tenant.school_id', $school->id);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('tenant.guardians.index'))
        ->assertStatus(200);
});

it('GET index retorna 302 para usuário não autenticado', function (): void {
    $school = makeSchoolForGuardianTests();

    $this->get(route('tenant.guardians.index'))
        ->assertStatus(302);
});

it('POST store cria um Guardian e redireciona', function (): void {
    $user = guardianMasterUser();
    $school = makeSchoolForGuardianTests();

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->post(route('tenant.guardians.store'), [
            'name' => 'Maria da Silva',
            'cpf' => '987.654.321-00',
            'phone' => '(11) 99999-9999',
            'email' => 'maria@escola.com',
        ])
        ->assertRedirect(route('tenant.guardians.index'));

    $this->assertDatabaseHas('guardians', [
        'school_id' => $school->id,
        'name' => 'Maria da Silva',
        'cpf' => '987.654.321-00',
    ]);
});

it('CPF duplicado no mesmo tenant retorna 422', function (): void {
    $user = guardianMasterUser();
    $school = makeSchoolForGuardianTests();
    makeGuardian($school, '987.654.321-00');

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('tenant.guardians.store'), [
            'name' => 'Outro Responsável',
            'cpf' => '987.654.321-00',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['cpf']);
});

it('CPF igual em tenant diferente é aceito', function (): void {
    $user = guardianMasterUser();
    $school1 = makeSchoolForGuardianTests();
    $school2 = makeSchoolForGuardianTests();
    makeGuardian($school1, '987.654.321-00');

    app()->instance('tenant.school_id', $school2->id);

    $this->actingAs($user)
        ->post(route('tenant.guardians.store'), [
            'name' => 'Responsável Outro Tenant',
            'cpf' => '987.654.321-00',
        ])
        ->assertRedirect(route('tenant.guardians.index'));

    $this->assertDatabaseHas('guardians', [
        'school_id' => $school2->id,
        'name' => 'Responsável Outro Tenant',
        'cpf' => '987.654.321-00',
    ]);
});

it('GET lookup retorna 200 com Guardian existente', function (): void {
    $user = guardianMasterUser();
    $school = makeSchoolForGuardianTests();
    makeGuardian($school, '987.654.321-00');

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->getJson(route('tenant.guardians.lookup', ['987.654.321-00']))
        ->assertStatus(200)
        ->assertJsonFragment(['cpf' => '987.654.321-00']);
});

it('GET lookup retorna 404 para CPF inexistente', function (): void {
    $user = guardianMasterUser();
    $school = makeSchoolForGuardianTests();

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->getJson(route('tenant.guardians.lookup', ['000.000.000-00']))
        ->assertStatus(404);
});

it('PUT update atualiza o Guardian e redireciona', function (): void {
    $user = guardianMasterUser();
    $school = makeSchoolForGuardianTests();
    $guardian = makeGuardian($school, '987.654.321-00');

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->put(route('tenant.guardians.update', [$guardian]), [
            'name' => 'Maria Atualizada',
            'cpf' => '987.654.321-00',
        ])
        ->assertRedirect(route('tenant.guardians.index'));

    $this->assertDatabaseHas('guardians', [
        'id' => $guardian->id,
        'name' => 'Maria Atualizada',
    ]);
});

it('DELETE destroy remove o Guardian e redireciona', function (): void {
    $user = guardianMasterUser();
    $school = makeSchoolForGuardianTests();
    $guardian = makeGuardian($school, '987.654.321-00');

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->delete(route('tenant.guardians.destroy', [$guardian]))
        ->assertRedirect(route('tenant.guardians.index'));

    $this->assertDatabaseMissing('guardians', [
        'id' => $guardian->id,
    ]);
});

it('findOrCreate retorna existente se CPF já cadastrado', function (): void {
    $school = makeSchoolForGuardianTests();
    $existing = makeGuardian($school, '987.654.321-00');

    app()->instance('tenant.school_id', $school->id);

    $service = app(GuardianService::class);
    $result = $service->findOrCreate([
        'name' => 'Outro Nome',
        'cpf' => '987.654.321-00',
    ]);

    expect($result->id)->toBe($existing->id);
    expect(Guardian::withoutTenantScope()->where('school_id', $school->id)->count())->toBe(1);
});

it('POST store cria um Guardian com campos de endereço e persiste', function (): void {
    $user = guardianMasterUser();
    $school = makeSchoolForGuardianTests();

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->post(route('tenant.guardians.store'), [
            'name' => 'José com Endereço',
            'cpf' => '123.456.789-00',
            'zip_code' => '01310100',
            'street' => 'Av. Paulista',
            'number' => '1000',
            'state' => 'SP',
            'city' => 'São Paulo',
            'neighborhood' => 'Bela Vista',
        ])
        ->assertStatus(302);

    $this->assertDatabaseHas('guardians', [
        'school_id' => $school->id,
        'name' => 'José com Endereço',
        'zip_code' => '01310100',
        'state' => 'SP',
    ]);
});
