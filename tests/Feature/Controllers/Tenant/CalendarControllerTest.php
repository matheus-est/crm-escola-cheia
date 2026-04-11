<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers\Tenant;

use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Event;
use App\Models\Opportunity;
use App\Models\Role;
use App\Models\School;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->seed(ModuleSeeder::class);
    $this->seed(PermissionSeeder::class);

    $this->school = School::factory()->create();
    $this->role   = Role::where('name', 'Gestor')->firstOrFail();
    $this->user   = User::factory()->create([
        'role_id'           => $this->role->id,
        'school_current_id' => $this->school->id,
    ]);
    $this->user->schools()->attach($this->school->id, ['is_active' => true]);

    app()->instance('tenant.school_id', $this->school->id);
});

afterEach(function (): void {
    app()->forgetInstance('tenant.school_id');
});

it('renders the calendar page', function (): void {
    $this->withoutVite()
        ->actingAs($this->user)
        ->get(route('tenant.calendar.index'))
        ->assertOk();
});

it('excludes non-schedule tasks', function (): void {
    $opportunity = Opportunity::factory()->create(['school_id' => $this->school->id]);

    Task::factory()->create([
        'school_id'      => $this->school->id,
        'opportunity_id' => $opportunity->id,
        'type'           => TaskType::RetornoLigacao->value,
        'status'         => TaskStatus::Open->value,
        'due_at'         => now()->startOfMonth()->addDays(5),
    ]);

    $dateFrom = now()->startOfMonth()->toDateTimeString();
    $dateTo   = now()->endOfMonth()->toDateTimeString();

    $response = $this->actingAs($this->user)
        ->getJson(route('tenant.calendar.entries', [
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ]));

    $response->assertOk();
    expect($response->json())->toBeEmpty();
});

it('includes schedule tasks within date range', function (): void {
    $opportunity = Opportunity::factory()->create(['school_id' => $this->school->id]);

    Task::factory()->create([
        'school_id'      => $this->school->id,
        'opportunity_id' => $opportunity->id,
        'type'           => TaskType::Agendamento->value,
        'status'         => TaskStatus::Open->value,
        'due_at'         => now()->startOfMonth()->addDays(5),
    ]);

    $dateFrom = now()->startOfMonth()->toDateTimeString();
    $dateTo   = now()->endOfMonth()->toDateTimeString();

    $response = $this->actingAs($this->user)
        ->getJson(route('tenant.calendar.entries', [
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ]));

    $response->assertOk();
    expect($response->json())->toHaveCount(1);
    expect($response->json()[0]['type'])->toBe('task');
    expect($response->json()[0]['sub_type'])->toBe('agendamento');
});

it('excludes tasks outside the date range', function (): void {
    $opportunity = Opportunity::factory()->create(['school_id' => $this->school->id]);

    Task::factory()->create([
        'school_id'      => $this->school->id,
        'opportunity_id' => $opportunity->id,
        'type'           => TaskType::Agendamento->value,
        'status'         => TaskStatus::Open->value,
        'due_at'         => now()->addYear(),
    ]);

    $dateFrom = now()->startOfMonth()->toDateTimeString();
    $dateTo   = now()->endOfMonth()->toDateTimeString();

    $response = $this->actingAs($this->user)
        ->getJson(route('tenant.calendar.entries', [
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ]));

    $response->assertOk();
    expect($response->json())->toBeEmpty();
});

it('includes events within date range', function (): void {
    Event::factory()->create([
        'school_id'  => $this->school->id,
        'event_date' => now()->startOfMonth()->addDays(3),
        'has_no_date' => false,
        'title'      => 'Palestra Interna',
    ]);

    $dateFrom = now()->startOfMonth()->toDateTimeString();
    $dateTo   = now()->endOfMonth()->toDateTimeString();

    $response = $this->actingAs($this->user)
        ->getJson(route('tenant.calendar.entries', [
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ]));

    $response->assertOk();
    expect($response->json())->toHaveCount(1);
    expect($response->json()[0]['type'])->toBe('event');
    expect($response->json()[0]['title'])->toBe('Palestra Interna');
});

it('excludes events with has_no_date true', function (): void {
    Event::factory()->create([
        'school_id'   => $this->school->id,
        'event_date'  => now()->startOfMonth()->addDays(3),
        'has_no_date' => true,
        'title'       => 'Evento Sem Data',
    ]);

    $dateFrom = now()->startOfMonth()->toDateTimeString();
    $dateTo   = now()->endOfMonth()->toDateTimeString();

    $response = $this->actingAs($this->user)
        ->getJson(route('tenant.calendar.entries', [
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ]));

    $response->assertOk();
    expect($response->json())->toBeEmpty();
});

it('filters by assigned_user_uuid', function (): void {
    $otherUser = User::factory()->create([
        'role_id'           => $this->role->id,
        'school_current_id' => $this->school->id,
    ]);
    $otherUser->schools()->attach($this->school->id, ['is_active' => true]);

    $opportunity = Opportunity::factory()->create(['school_id' => $this->school->id]);

    // Task for $this->user
    Task::factory()->create([
        'school_id'        => $this->school->id,
        'opportunity_id'   => $opportunity->id,
        'type'             => TaskType::Agendamento->value,
        'status'           => TaskStatus::Open->value,
        'assigned_user_id' => $this->user->id,
        'due_at'           => now()->startOfMonth()->addDays(5),
    ]);

    // Task for $otherUser
    Task::factory()->create([
        'school_id'        => $this->school->id,
        'opportunity_id'   => $opportunity->id,
        'type'             => TaskType::LembreteAgenda->value,
        'status'           => TaskStatus::Open->value,
        'assigned_user_id' => $otherUser->id,
        'due_at'           => now()->startOfMonth()->addDays(6),
    ]);

    $dateFrom = now()->startOfMonth()->toDateTimeString();
    $dateTo   = now()->endOfMonth()->toDateTimeString();

    $response = $this->actingAs($this->user)
        ->getJson(route('tenant.calendar.entries', [
            'date_from'          => $dateFrom,
            'date_to'            => $dateTo,
            'assigned_user_uuid' => $this->user->uuid,
        ]));

    $response->assertOk();
    expect($response->json())->toHaveCount(1);
    expect($response->json()[0]['sub_type'])->toBe('agendamento');
});

it('forces comercial to see only their own tasks', function (): void {
    $comercialRole = Role::where('name', 'Comercial')->firstOrFail();

    $comercialUser = User::factory()->create([
        'role_id'           => $comercialRole->id,
        'school_current_id' => $this->school->id,
    ]);
    $comercialUser->schools()->attach($this->school->id, ['is_active' => true]);

    $otherUser = User::factory()->create([
        'role_id'           => $this->role->id,
        'school_current_id' => $this->school->id,
    ]);
    $otherUser->schools()->attach($this->school->id, ['is_active' => true]);

    $opportunity = Opportunity::factory()->create(['school_id' => $this->school->id]);

    // Task for comercialUser
    Task::factory()->create([
        'school_id'        => $this->school->id,
        'opportunity_id'   => $opportunity->id,
        'type'             => TaskType::Agendamento->value,
        'status'           => TaskStatus::Open->value,
        'assigned_user_id' => $comercialUser->id,
        'due_at'           => now()->startOfMonth()->addDays(5),
    ]);

    // Task for otherUser
    Task::factory()->create([
        'school_id'        => $this->school->id,
        'opportunity_id'   => $opportunity->id,
        'type'             => TaskType::Agendamento->value,
        'status'           => TaskStatus::Open->value,
        'assigned_user_id' => $otherUser->id,
        'due_at'           => now()->startOfMonth()->addDays(6),
    ]);

    $dateFrom = now()->startOfMonth()->toDateTimeString();
    $dateTo   = now()->endOfMonth()->toDateTimeString();

    $response = $this->actingAs($comercialUser)
        ->getJson(route('tenant.calendar.entries', [
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ]));

    $response->assertOk();
    expect($response->json())->toHaveCount(1);
    expect($response->json()[0]['assigned_user'])->not->toBeNull();
});

it('entries endpoint returns json', function (): void {
    $dateFrom = now()->startOfMonth()->toDateTimeString();
    $dateTo   = now()->endOfMonth()->toDateTimeString();

    $response = $this->actingAs($this->user)
        ->getJson(route('tenant.calendar.entries', [
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ]));

    $response->assertOk();
    $response->assertJsonStructure([]);
});
