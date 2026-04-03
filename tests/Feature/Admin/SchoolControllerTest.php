<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

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
        'razao_social' => 'Escola Teste',
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
            'razao_social' => 'Escola Atualizada',
        ])
        ->assertRedirect(route('admin.schools.edit', $school));

    $this->assertDatabaseHas('schools', ['razao_social' => 'Escola Atualizada']);
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
