<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    // Remove tenant binding between tests so isolation is guaranteed.
    app()->forgetInstance('tenant.school_id');
});

it('redirects unauthenticated requests to tenant.dashboard', function (): void {
    $this->get('/t/some-uuid/dashboard')
        ->assertStatus(302);
});

it('denies access to tenant.dashboard because currentSchool returns null without School model', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/t/some-uuid/dashboard')
        ->assertStatus(403);
});

it('does not bind tenant.school_id when currentSchool returns null', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/t/some-uuid/dashboard');

    expect(app()->bound('tenant.school_id'))->toBeFalse();
});
