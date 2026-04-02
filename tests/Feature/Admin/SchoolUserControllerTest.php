<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function schoolUserControllerUser(string $roleName): User
{
    $role = Role::firstOrCreate(
        ['name' => $roleName],
        ['description' => $roleName, 'is_default' => false],
    );

    $user = User::factory()->create();
    $user->role()->associate($role)->save();

    return $user;
}

function makeSchoolForUserTests(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) $counter, 14, '0', STR_PAD_LEFT),
        'razao_social' => 'Escola Teste '.$counter,
    ]);
}

function makeTenantUser(string $roleName): User
{
    $role = Role::firstOrCreate(
        ['name' => $roleName],
        ['description' => $roleName, 'is_default' => false],
    );

    $user = User::factory()->create();
    $user->role()->associate($role)->save();

    return $user;
}

// Cenário 1: Admin vincula user Gestor com role Gestor
it('Admin faz POST com user Gestor e role Gestor e cria vínculo', function (): void {
    $admin = schoolUserControllerUser('Admin');
    $school = makeSchoolForUserTests();
    $gestor = makeTenantUser('Gestor');
    $gestorRole = Role::firstOrCreate(['name' => 'Gestor'], ['description' => 'Gestor', 'is_default' => false]);

    $this->actingAs($admin)
        ->post(route('admin.schools.users.store', $school), [
            'user_id' => $gestor->uuid,
            'role_id' => $gestorRole->uuid,
        ])
        ->assertRedirect(route('admin.schools.edit', $school));

    $this->assertDatabaseHas('school_user', [
        'school_id' => $school->id,
        'user_id' => $gestor->id,
    ]);
});

// Cenário 2: Admin vincula user Comercial com role Comercial
it('Admin faz POST com user Comercial e role Comercial e cria vínculo', function (): void {
    $admin = schoolUserControllerUser('Admin');
    $school = makeSchoolForUserTests();
    $comercial = makeTenantUser('Comercial');
    $comercialRole = Role::firstOrCreate(['name' => 'Comercial'], ['description' => 'Comercial', 'is_default' => false]);

    $this->actingAs($admin)
        ->post(route('admin.schools.users.store', $school), [
            'user_id' => $comercial->uuid,
            'role_id' => $comercialRole->uuid,
        ])
        ->assertRedirect(route('admin.schools.edit', $school));

    $this->assertDatabaseHas('school_user', [
        'school_id' => $school->id,
        'user_id' => $comercial->id,
    ]);
});

// Cenário 3: Admin tenta vincular role Master → 422
it('Admin tenta vincular role Master e recebe 422 sem inserir em school_user', function (): void {
    $admin = schoolUserControllerUser('Admin');
    $school = makeSchoolForUserTests();
    $masterRole = Role::firstOrCreate(['name' => 'Master'], ['description' => 'Master', 'is_default' => false]);
    $targetUser = makeTenantUser('Gestor');

    $this->actingAs($admin)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.schools.users.store', $school), [
            'user_id' => $targetUser->uuid,
            'role_id' => $masterRole->uuid,
        ])
        ->assertStatus(422);

    $this->assertDatabaseMissing('school_user', [
        'school_id' => $school->id,
        'user_id' => $targetUser->id,
    ]);
});

// Cenário 4: Admin tenta vincular role Admin → 422
it('Admin tenta vincular role Admin e recebe 422 sem inserir em school_user', function (): void {
    $admin = schoolUserControllerUser('Admin');
    $school = makeSchoolForUserTests();
    $adminRole = Role::firstOrCreate(['name' => 'Admin'], ['description' => 'Admin', 'is_default' => false]);
    $targetUser = makeTenantUser('Gestor');

    $this->actingAs($admin)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.schools.users.store', $school), [
            'user_id' => $targetUser->uuid,
            'role_id' => $adminRole->uuid,
        ])
        ->assertStatus(422);

    $this->assertDatabaseMissing('school_user', [
        'school_id' => $school->id,
        'user_id' => $targetUser->id,
    ]);
});

// Cenário 5: Admin tenta vincular role Operacao → 422
it('Admin tenta vincular role Operacao e recebe 422 sem inserir em school_user', function (): void {
    $admin = schoolUserControllerUser('Admin');
    $school = makeSchoolForUserTests();
    $operacaoRole = Role::firstOrCreate(['name' => 'Operacao'], ['description' => 'Operacao', 'is_default' => false]);
    $targetUser = makeTenantUser('Gestor');

    $this->actingAs($admin)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.schools.users.store', $school), [
            'user_id' => $targetUser->uuid,
            'role_id' => $operacaoRole->uuid,
        ])
        ->assertStatus(422);

    $this->assertDatabaseMissing('school_user', [
        'school_id' => $school->id,
        'user_id' => $targetUser->id,
    ]);
});

// Cenário 6: Attach duplicado → 422
it('Admin faz POST com mesmo user duas vezes e recebe 422', function (): void {
    $admin = schoolUserControllerUser('Admin');
    $school = makeSchoolForUserTests();
    $gestor = makeTenantUser('Gestor');
    $gestorRole = Role::firstOrCreate(['name' => 'Gestor'], ['description' => 'Gestor', 'is_default' => false]);

    $school->users()->attach($gestor->id, ['is_active' => true]);

    $this->actingAs($admin)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.schools.users.store', $school), [
            'user_id' => $gestor->uuid,
            'role_id' => $gestorRole->uuid,
        ])
        ->assertStatus(422);
});

// Cenário 7: DELETE remove vínculo existente
it('Admin faz DELETE de vínculo existente e remove de school_user', function (): void {
    $admin = schoolUserControllerUser('Admin');
    $school = makeSchoolForUserTests();
    $gestor = makeTenantUser('Gestor');

    $school->users()->attach($gestor->id, ['is_active' => true]);

    $this->actingAs($admin)
        ->delete(route('admin.schools.users.destroy', [$school, $gestor->uuid]))
        ->assertRedirect(route('admin.schools.edit', $school));

    $this->assertDatabaseMissing('school_user', [
        'school_id' => $school->id,
        'user_id' => $gestor->id,
    ]);
});

// Cenário 8: Operacao recebe 403 no POST
it('Operacao recebe 403 ao tentar vincular usuário a escola', function (): void {
    $operacao = schoolUserControllerUser('Operacao');
    $school = makeSchoolForUserTests();
    $gestor = makeTenantUser('Gestor');
    $gestorRole = Role::firstOrCreate(['name' => 'Gestor'], ['description' => 'Gestor', 'is_default' => false]);

    $this->actingAs($operacao)
        ->post(route('admin.schools.users.store', $school), [
            'user_id' => $gestor->uuid,
            'role_id' => $gestorRole->uuid,
        ])
        ->assertStatus(403);
});

// Cenário 9: Operacao recebe 403 no DELETE
it('Operacao recebe 403 ao tentar desvincular usuário de escola', function (): void {
    $operacao = schoolUserControllerUser('Operacao');
    $school = makeSchoolForUserTests();
    $gestor = makeTenantUser('Gestor');

    $school->users()->attach($gestor->id, ['is_active' => true]);

    $this->actingAs($operacao)
        ->delete(route('admin.schools.users.destroy', [$school, $gestor->uuid]))
        ->assertStatus(403);
});
