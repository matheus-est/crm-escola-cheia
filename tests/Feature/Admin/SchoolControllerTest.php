<?php

declare(strict_types=1);

use App\Mail\WelcomeSchoolUserMail;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function schoolControllerUser(string $roleName): User
{
    $role = Role::firstOrCreate(
        ['name' => $roleName],
        ['description' => $roleName, 'is_default' => false],
    );

    $user = User::factory()->create();
    $user->role()->associate($role)->save();

    return $user;
}

function makeSchool(): School
{
    return School::create([
        'cnpj' => '12345678000195',
        'legal_name' => 'Escola Teste',
    ]);
}

it('Operacao recebe 403 ao tentar excluir escola', function (): void {
    $user = schoolControllerUser('Operacao');
    $school = makeSchool();

    $this->actingAs($user)
        ->delete(route('admin.schools.destroy', $school))
        ->assertStatus(403);
});

it('Admin consegue editar escola', function (): void {
    $user = schoolControllerUser('Admin');
    $school = makeSchool();

    $this->actingAs($user)
        ->put(route('admin.schools.update', $school), [
            'cnpj' => '12345678000195',
            'legal_name' => 'Escola Atualizada',
        ])
        ->assertRedirect(route('admin.schools.edit', $school));

    $this->assertDatabaseHas('schools', ['legal_name' => 'Escola Atualizada']);
});

it('Master consegue excluir escola', function (): void {
    $user = schoolControllerUser('Master');
    $school = makeSchool();

    $this->actingAs($user)
        ->delete(route('admin.schools.destroy', $school))
        ->assertRedirect(route('admin.schools.index'));

    $this->assertDatabaseMissing('schools', ['id' => $school->id]);
});

it('Master consegue excluir escola via confirmDelete com senha correta', function (): void {
    $user = schoolControllerUser('Master');
    $user->password = Hash::make('senha123');
    $user->save();

    $school = makeSchool();

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.schools.confirmDelete', $school), ['password' => 'senha123'])
        ->assertRedirect(route('admin.schools.index'));

    $this->assertDatabaseMissing('schools', ['id' => $school->id]);
});

it('confirmDelete retorna 422 com senha incorreta', function (): void {
    $user = schoolControllerUser('Master');
    $user->password = Hash::make('senha123');
    $user->save();

    $school = makeSchool();

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.schools.confirmDelete', $school), ['password' => 'errada'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

it('Operacao recebe 403 ao tentar confirmDelete de escola', function (): void {
    $user = schoolControllerUser('Operacao');
    $user->password = Hash::make('senha123');
    $user->save();

    $school = makeSchool();

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('admin.schools.confirmDelete', $school), ['password' => 'senha123'])
        ->assertStatus(403);
});

it('lookupCnpj retorna dados para CNPJ válido', function (): void {
    $user = schoolControllerUser('Master');

    Http::fake([
        'brasilapi.com.br/*' => Http::response([
            'razao_social' => 'EMPRESA TESTE LTDA',
            'cnpj' => '12345678000195',
        ], 200),
    ]);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->get(route('admin.schools.cnpjLookup', '12345678000195'))
        ->assertOk()
        ->assertJsonFragment(['legal_name' => 'EMPRESA TESTE LTDA']);
});

it('lookupCnpj retorna 422 para CNPJ inválido', function (): void {
    $user = schoolControllerUser('Master');

    Http::fake([
        'brasilapi.com.br/*' => Http::response(['message' => 'CNPJ inválido'], 404),
    ]);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->get(route('admin.schools.cnpjLookup', '00000000000000'))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['cnpj']);
});

it('store redireciona para edit após criar escola', function (): void {
    $user = schoolControllerUser('Master');

    $this->actingAs($user)
        ->post(route('admin.schools.store'), [
            'cnpj' => '12345678000195',
            'legal_name' => 'Nova Escola',
        ])
        ->assertRedirectContains('/edit');

    $this->assertDatabaseHas('schools', ['legal_name' => 'Nova Escola']);
});

it('create page passa availableRoles como prop', function (): void {
    $user = schoolControllerUser('Master');
    Role::firstOrCreate(['name' => 'Gestor'], ['description' => 'Gestor', 'is_default' => false]);
    Role::firstOrCreate(['name' => 'Comercial'], ['description' => 'Comercial', 'is_default' => false]);

    $this->actingAs($user)
        ->get(route('admin.schools.create'))
        ->assertInertia(fn ($page) => $page->has('availableRoles'));
});

it('edit page passa availableRoles como prop', function (): void {
    $user = schoolControllerUser('Master');
    $school = makeSchool();
    Role::firstOrCreate(['name' => 'Gestor'], ['description' => 'Gestor', 'is_default' => false]);
    Role::firstOrCreate(['name' => 'Comercial'], ['description' => 'Comercial', 'is_default' => false]);

    $this->actingAs($user)
        ->get(route('admin.schools.edit', $school))
        ->assertInertia(fn ($page) => $page->has('availableRoles'));
});

it('storeOrCreate vincula usuário existente à escola', function (): void {
    $master = schoolControllerUser('Master');
    $school = makeSchool();

    $gestor = Role::firstOrCreate(['name' => 'Gestor'], ['description' => 'Gestor', 'is_default' => false]);
    $existingUser = User::factory()->create(['email' => 'existente@teste.com']);

    $this->actingAs($master)
        ->post(route('admin.schools.users.storeOrCreate', $school), [
            'name' => 'Qualquer',
            'email' => 'existente@teste.com',
            'role_id' => $gestor->uuid,
        ])
        ->assertRedirect(route('admin.schools.edit', $school));

    $this->assertDatabaseHas('school_user', ['school_id' => $school->id, 'user_id' => $existingUser->id]);
});

it('storeOrCreate cria novo usuário e envia e-mail', function (): void {
    Mail::fake();

    $master = schoolControllerUser('Master');
    $school = makeSchool();
    $gestor = Role::firstOrCreate(['name' => 'Gestor'], ['description' => 'Gestor', 'is_default' => false]);

    $this->actingAs($master)
        ->post(route('admin.schools.users.storeOrCreate', $school), [
            'name' => 'Novo Responsável',
            'email' => 'novo@teste.com',
            'role_id' => $gestor->uuid,
        ])
        ->assertRedirect(route('admin.schools.edit', $school));

    $this->assertDatabaseHas('users', ['email' => 'novo@teste.com']);
    $novoUser = User::where('email', 'novo@teste.com')->first();
    $this->assertDatabaseHas('school_user', ['school_id' => $school->id, 'user_id' => $novoUser->id]);
    Mail::assertQueued(WelcomeSchoolUserMail::class);
});

it('storeOrCreate restaura usuário deletado e vincula', function (): void {
    $master = schoolControllerUser('Master');
    $school = makeSchool();
    $gestor = Role::firstOrCreate(['name' => 'Gestor'], ['description' => 'Gestor', 'is_default' => false]);

    $deletedUser = User::factory()->create(['email' => 'deletado@teste.com']);
    $deletedUser->delete();

    $this->assertSoftDeleted('users', ['email' => 'deletado@teste.com']);

    $this->actingAs($master)
        ->post(route('admin.schools.users.storeOrCreate', $school), [
            'name' => 'Qualquer',
            'email' => 'deletado@teste.com',
            'role_id' => $gestor->uuid,
        ])
        ->assertRedirect(route('admin.schools.edit', $school));

    $this->assertDatabaseHas('users', ['email' => 'deletado@teste.com', 'deleted_at' => null]);
    $restored = User::where('email', 'deletado@teste.com')->first();
    $this->assertDatabaseHas('school_user', ['school_id' => $school->id, 'user_id' => $restored->id]);
});
