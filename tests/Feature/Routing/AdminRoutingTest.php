<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Helper to create a user with the given role name.
 */
function userWithRole(string $roleName): User
{
    $role = Role::firstOrCreate(
        ['name' => $roleName],
        ['description' => $roleName, 'is_default' => false],
    );

    $user = User::factory()->create();
    $user->role()->associate($role)->save();

    return $user;
}

it('redirects unauthenticated requests to admin.dashboard', function (): void {
    $this->get(route('admin.dashboard'))
        ->assertStatus(302);
});

it('denies access to admin.dashboard for role Gestor', function (): void {
    $this->actingAs(userWithRole('Gestor'))
        ->get(route('admin.dashboard'))
        ->assertStatus(403);
});

it('denies access to admin.dashboard for role Comercial', function (): void {
    $this->actingAs(userWithRole('Comercial'))
        ->get(route('admin.dashboard'))
        ->assertStatus(403);
});

it('denies access to admin.dashboard for role User', function (): void {
    $this->actingAs(userWithRole('User'))
        ->get(route('admin.dashboard'))
        ->assertStatus(403);
});

it('denies access to admin.dashboard for user without role', function (): void {
    $user = User::factory()->create(['role_id' => null]);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertStatus(403);
});

it('grants access to admin.dashboard for role Master', function (): void {
    $this->actingAs(userWithRole('Master'))
        ->get(route('admin.dashboard'))
        ->assertStatus(200);
});

it('grants access to admin.dashboard for role Admin', function (): void {
    $this->actingAs(userWithRole('Admin'))
        ->get(route('admin.dashboard'))
        ->assertStatus(200);
});

it('grants access to admin.dashboard for role Operacao', function (): void {
    $this->actingAs(userWithRole('Operacao'))
        ->get(route('admin.dashboard'))
        ->assertStatus(200);
});
