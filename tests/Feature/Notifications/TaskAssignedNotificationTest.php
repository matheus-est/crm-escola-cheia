<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Grade;
use App\Models\Opportunity;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\Segment;
use App\Models\User;
use App\Notifications\TaskAssignedNotification;
use App\Services\Task\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('tenant.school_id');
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function notifAssignedMakeSchool(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) ($counter + 70000), 14, '0', STR_PAD_LEFT),
        'legal_name' => "Escola NotifAssigned {$counter}",
        'slug' => "escola-notif-assigned-{$counter}",
    ]);
}

function notifAssignedMakeUser(School $school, string $roleName = 'Gestor'): User
{
    $role = Role::firstOrCreate(
        ['name' => $roleName],
        ['description' => $roleName, 'is_default' => false],
    );
    $user = User::factory()->create();
    $user->role()->associate($role)->save();
    $school->users()->attach($user->id, ['is_active' => true]);

    return $user;
}

function notifAssignedMakeOpportunity(School $school): Opportunity
{
    $segment = Segment::firstOrCreate(
        ['name' => 'Ensino Fundamental'],
        ['name' => 'Ensino Fundamental'],
    );

    $grade = Grade::withoutTenantScope()->firstOrCreate([
        'school_id' => $school->id,
        'segment_id' => $segment->id,
        'name' => '1º Ano',
    ]);

    $schoolYear = SchoolYear::withoutTenantScope()->firstOrCreate(
        ['school_id' => $school->id, 'name' => '2025'],
        [
            'start' => '2025-01-01',
            'end' => '2025-12-31',
            'status' => 'planejamento',
        ]
    );

    return Opportunity::withoutTenantScope()->create([
        'school_id' => $school->id,
        'grade_id' => $grade->id,
        'school_year_id' => $schoolYear->id,
        'status' => 'cadastro_inicial',
        'renitente_count' => 0,
    ]);
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

it('dispatches TaskAssignedNotification when task has assigned_user_id', function (): void {
    Notification::fake();

    $school = notifAssignedMakeSchool();
    $assignedUser = notifAssignedMakeUser($school);
    $opportunity = notifAssignedMakeOpportunity($school);

    app()->instance('tenant.school_id', $school->id);

    $service = app(TaskService::class);
    $service->create($opportunity, [
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Open->value,
        'assigned_user_id' => $assignedUser->id,
        'due_at' => now()->addDay(),
    ]);

    Notification::assertSentTo($assignedUser, TaskAssignedNotification::class);
});

it('does not dispatch TaskAssignedNotification when task has no assigned_user_id', function (): void {
    Notification::fake();

    $school = notifAssignedMakeSchool();
    $opportunity = notifAssignedMakeOpportunity($school);

    app()->instance('tenant.school_id', $school->id);

    $service = app(TaskService::class);
    $service->create($opportunity, [
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->addDay(),
    ]);

    Notification::assertNothingSent();
});
