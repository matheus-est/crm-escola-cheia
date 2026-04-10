<?php

declare(strict_types=1);

use App\Enums\SchoolStatus;
use App\Http\Middleware\SetActiveTenant;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function activeSchoolUser(string $roleName): User
{
    $role = Role::firstOrCreate(
        ['name' => $roleName],
        ['description' => $roleName, 'is_default' => false],
    );

    $user = User::factory()->create();
    $user->role()->associate($role)->save();

    return $user;
}

function makeActiveSchool(): School
{
    return School::query()->create([
        'cnpj' => fake()->numerify('##############'),
        'legal_name' => fake()->company(),
        'slug' => fake()->unique()->slug(),
        'status' => SchoolStatus::Active->value,
    ]);
}

function makeInactiveSchool(): School
{
    return School::query()->create([
        'cnpj' => fake()->numerify('##############'),
        'legal_name' => fake()->company(),
        'slug' => fake()->unique()->slug(),
        'status' => SchoolStatus::Inactive->value,
    ]);
}

it('usuário não autenticado é redirecionado para login', function (): void {
    $school = makeActiveSchool();

    $this->post(route('active-school.store'), ['school_uuid' => $school->uuid])
        ->assertRedirect(route('login'));
});

it('UUID inválido retorna 422', function (): void {
    $user = activeSchoolUser('Master');

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('active-school.store'), ['school_uuid' => 'uuid-inexistente'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['school_uuid']);
});

it('cross-tenant pode trocar para escola ativa', function (): void {
    $user = activeSchoolUser('Master');
    $school = makeActiveSchool();

    $this->actingAs($user)
        ->post(route('active-school.store'), ['school_uuid' => $school->uuid])
        ->assertRedirect();

    expect(session('active_school_uuid'))->toBe($school->uuid);
});

it('cross-tenant não pode trocar para escola inativa', function (): void {
    $user = activeSchoolUser('Admin');
    $school = makeInactiveSchool();

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('active-school.store'), ['school_uuid' => $school->uuid])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['school_uuid']);
});

it('não-cross-tenant pode trocar para escola com vínculo ativo', function (): void {
    $user = activeSchoolUser('Gestor');
    $school = makeActiveSchool();

    $school->users()->attach($user->id, ['is_active' => true]);

    $this->actingAs($user)
        ->post(route('active-school.store'), ['school_uuid' => $school->uuid])
        ->assertRedirect();

    expect(session('active_school_uuid'))->toBe($school->uuid);
});

it('não-cross-tenant não pode acessar escola sem vínculo no pivot', function (): void {
    $user = activeSchoolUser('Gestor');
    $school = makeActiveSchool();

    $this->withoutMiddleware(SetActiveTenant::class)
        ->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('active-school.store'), ['school_uuid' => $school->uuid])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['school_uuid']);
});

it('não-cross-tenant não pode acessar escola com vínculo inativo', function (): void {
    $user = activeSchoolUser('Comercial');
    $school = makeActiveSchool();

    $school->users()->attach($user->id, ['is_active' => false]);

    $this->withoutMiddleware(SetActiveTenant::class)
        ->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('active-school.store'), ['school_uuid' => $school->uuid])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['school_uuid']);
});
