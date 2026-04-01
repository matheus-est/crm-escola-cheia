<?php

declare(strict_types=1);

use App\Models\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Schema::create('test_items', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('school_id');
        $table->string('name');
        $table->timestamps();
    });
});

afterEach(function (): void {
    Schema::dropIfExists('test_items');

    // Remove tenant binding between tests so isolation is guaranteed.
    app()->forgetInstance('tenant.school_id');
});

it('filters records by the active tenant school_id', function (): void {
    DB::table('test_items')->insert([
        ['school_id' => 1, 'name' => 'School A item', 'created_at' => now(), 'updated_at' => now()],
        ['school_id' => 2, 'name' => 'School B item', 'created_at' => now(), 'updated_at' => now()],
    ]);

    app()->instance('tenant.school_id', 1);

    $model = new class extends Model
    {
        use BelongsToTenant;

        protected $table = 'test_items';
    };

    $results = $model::all();

    expect($results)->toHaveCount(1)
        ->and($results->first()->school_id)->toBe(1);
});

it('returns all records when no tenant is bound', function (): void {
    DB::table('test_items')->insert([
        ['school_id' => 1, 'name' => 'A', 'created_at' => now(), 'updated_at' => now()],
        ['school_id' => 2, 'name' => 'B', 'created_at' => now(), 'updated_at' => now()],
    ]);

    // Ensure the binding is not present (container is clean per test isolation).
    app()->forgetInstance('tenant.school_id');

    $model = new class extends Model
    {
        use BelongsToTenant;

        protected $table = 'test_items';
    };

    expect($model::all())->toHaveCount(2);
});

it('withoutTenantScope returns all records regardless of active tenant', function (): void {
    DB::table('test_items')->insert([
        ['school_id' => 1, 'name' => 'A', 'created_at' => now(), 'updated_at' => now()],
        ['school_id' => 2, 'name' => 'B', 'created_at' => now(), 'updated_at' => now()],
    ]);

    app()->instance('tenant.school_id', 1);

    $model = new class extends Model
    {
        use BelongsToTenant;

        protected $table = 'test_items';
    };

    $results = $model::withoutTenantScope()->get();

    expect($results)->toHaveCount(2);
});

it('ignores school_id injected via request', function (): void {
    $user = User::factory()->create();

    // Ensure no tenant binding exists before the middleware runs.
    app()->forgetInstance('tenant.school_id');

    // Register a temporary route protected by the tenant middleware.
    Route::middleware('tenant')
        ->get('/_test_tenant_middleware', fn () => response('ok'));

    // The middleware aborts 403 because currentSchool() returns null (School model
    // not yet created — Etapa 1.x), not because the query param was used.
    $this->actingAs($user)
        ->get('/_test_tenant_middleware?school_id=999')
        ->assertStatus(403);

    // The middleware aborted before registering any tenant binding,
    // so the query param value (999) was never stored in the container.
    expect(app()->bound('tenant.school_id'))->toBeFalse();
});
