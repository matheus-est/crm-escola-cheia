<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    // Remove tenant binding between tests so isolation is guaranteed.
    app()->forgetInstance('tenant.school_id');
});

it('returns 404 for unauthenticated requests to tenant.dashboard with nonexistent school uuid', function (): void {
    $this->get('/t/some-uuid/dashboard')
        ->assertStatus(404);
});

it('returns 404 when school_uuid does not exist in database', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/t/some-uuid/dashboard')
        ->assertStatus(404);
});

it('does not bind tenant.school_id when school_uuid does not exist in database', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/t/some-uuid/dashboard');

    expect(app()->bound('tenant.school_id'))->toBeFalse();
});
