<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('tenant.school_id');
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function notifCtrlMakeSchool(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) ($counter + 60000), 14, '0', STR_PAD_LEFT),
        'legal_name' => "Escola NotifCtrl {$counter}",
        'slug' => "escola-notif-ctrl-{$counter}",
    ]);
}

function notifCtrlMakeUser(School $school, string $roleName = 'Gestor'): User
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

function notifCtrlSeedNotification(User $user, bool $read = false): string
{
    $id = (string) Str::uuid();
    DatabaseNotification::create([
        'id' => $id,
        'type' => 'App\Notifications\TaskAssignedNotification',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id,
        'data' => json_encode(['task_uuid' => 'some-uuid']),
        'read_at' => $read ? now() : null,
    ]);

    return $id;
}

// ---------------------------------------------------------------------------
// GET /tenant/notifications/count
// ---------------------------------------------------------------------------

it('GET count retorna count de notificações não lidas', function (): void {
    Notification::fake();

    $school = notifCtrlMakeSchool();
    $user = notifCtrlMakeUser($school);

    app()->instance('tenant.school_id', $school->id);

    notifCtrlSeedNotification($user);

    $this->withoutVite()
        ->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->get(route('tenant.notifications.unread_count'))
        ->assertStatus(200)
        ->assertJson(['count' => 1]);
});

it('GET count retorna 0 quando não há notificações não lidas', function (): void {
    $school = notifCtrlMakeSchool();
    $user = notifCtrlMakeUser($school);

    app()->instance('tenant.school_id', $school->id);

    $this->withoutVite()
        ->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->get(route('tenant.notifications.unread_count'))
        ->assertStatus(200)
        ->assertJson(['count' => 0]);
});

// ---------------------------------------------------------------------------
// GET /tenant/notifications
// ---------------------------------------------------------------------------

it('GET index retorna lista paginada de notificações', function (): void {
    $school = notifCtrlMakeSchool();
    $user = notifCtrlMakeUser($school);

    app()->instance('tenant.school_id', $school->id);

    notifCtrlSeedNotification($user);

    $this->withoutVite()
        ->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->get(route('tenant.notifications.index'))
        ->assertStatus(200)
        ->assertJsonStructure(['data', 'total', 'per_page']);
});

// ---------------------------------------------------------------------------
// PATCH /tenant/notifications/{id}/read
// ---------------------------------------------------------------------------

it('PATCH mark_read define read_at na notificação do usuário', function (): void {
    $school = notifCtrlMakeSchool();
    $user = notifCtrlMakeUser($school);

    app()->instance('tenant.school_id', $school->id);

    $notificationId = notifCtrlSeedNotification($user);

    $this->withoutMiddleware(VerifyCsrfToken::class)
        ->withoutVite()
        ->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->patch(route('tenant.notifications.mark_read', ['id' => $notificationId]))
        ->assertStatus(204);

    $this->assertDatabaseMissing('notifications', [
        'id' => $notificationId,
        'read_at' => null,
    ]);
});

it('PATCH mark_read retorna 403 se a notificação pertence a outro usuário', function (): void {
    $school = notifCtrlMakeSchool();
    $user = notifCtrlMakeUser($school);
    $otherUser = notifCtrlMakeUser($school);

    app()->instance('tenant.school_id', $school->id);

    $notificationId = notifCtrlSeedNotification($otherUser);

    $this->withoutMiddleware(VerifyCsrfToken::class)
        ->withoutVite()
        ->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->patch(route('tenant.notifications.mark_read', ['id' => $notificationId]))
        ->assertStatus(403);
});

// ---------------------------------------------------------------------------
// PATCH /tenant/notifications/read-all
// ---------------------------------------------------------------------------

it('PATCH mark_all_read define read_at em todas as notificações não lidas', function (): void {
    $school = notifCtrlMakeSchool();
    $user = notifCtrlMakeUser($school);

    app()->instance('tenant.school_id', $school->id);

    foreach (range(1, 3) as $_) {
        notifCtrlSeedNotification($user);
    }

    $this->withoutMiddleware(VerifyCsrfToken::class)
        ->withoutVite()
        ->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->patch(route('tenant.notifications.mark_all_read'))
        ->assertStatus(204);

    $this->assertDatabaseCount('notifications', 3);

    $unread = DatabaseNotification::where('notifiable_id', $user->id)
        ->whereNull('read_at')
        ->count();

    expect($unread)->toBe(0);
});
