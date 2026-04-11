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
use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('tenant.school_id');
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function notifOverdueMakeSchool(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) ($counter + 80000), 14, '0', STR_PAD_LEFT),
        'legal_name' => "Escola NotifOverdue {$counter}",
        'slug' => "escola-notif-overdue-{$counter}",
    ]);
}

function notifOverdueMakeUser(School $school): User
{
    $role = Role::firstOrCreate(
        ['name' => 'Gestor'],
        ['description' => 'Gestor', 'is_default' => false],
    );
    $user = User::factory()->create();
    $user->role()->associate($role)->save();
    $school->users()->attach($user->id, ['is_active' => true]);

    return $user;
}

function notifOverdueMakeOpportunity(School $school, ?User $responsibleUser = null): Opportunity
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
        'responsible_user_id' => $responsibleUser?->id,
    ]);
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

it('command dispatches TaskOverdueNotification for overdue open task without notified_overdue_at', function (): void {
    Notification::fake();

    $school = notifOverdueMakeSchool();
    $user = notifOverdueMakeUser($school);
    $opportunity = notifOverdueMakeOpportunity($school, $user);

    Task::withoutGlobalScopes()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->subHour(),
    ]);

    $this->artisan('notifications:notify-overdue-tasks')
        ->assertSuccessful();

    Notification::assertSentTo($user, TaskOverdueNotification::class);
});

it('command stamps notified_overdue_at after dispatch', function (): void {
    Notification::fake();

    $school = notifOverdueMakeSchool();
    $user = notifOverdueMakeUser($school);
    $opportunity = notifOverdueMakeOpportunity($school, $user);

    $task = Task::withoutGlobalScopes()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->subHour(),
    ]);

    $this->artisan('notifications:notify-overdue-tasks')
        ->assertSuccessful();

    $this->assertDatabaseMissing('tasks', [
        'id' => $task->id,
        'notified_overdue_at' => null,
    ]);
});

it('command does not re-notify task that already has notified_overdue_at', function (): void {
    Notification::fake();

    $school = notifOverdueMakeSchool();
    $user = notifOverdueMakeUser($school);
    $opportunity = notifOverdueMakeOpportunity($school, $user);

    Task::withoutGlobalScopes()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->subHour(),
        'notified_overdue_at' => now()->subMinutes(30),
    ]);

    $this->artisan('notifications:notify-overdue-tasks')
        ->assertSuccessful();

    Notification::assertNothingSent();
});
