<?php

declare(strict_types=1);

use App\Models\Role;
use App\Models\Room;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('tenant.school_id');
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function roomGestorUser(School $school): User
{
    $role = Role::firstOrCreate(
        ['name' => 'Gestor'],
        ['description' => 'Gestor', 'is_default' => false],
    );

    $user = User::factory()->create(['school_current_id' => $school->id]);
    $user->role()->associate($role)->save();
    $school->users()->attach($user->id, ['is_active' => true]);

    return $user;
}

function roomComercialUser(School $school): User
{
    $role = Role::firstOrCreate(
        ['name' => 'Comercial'],
        ['description' => 'Comercial', 'is_default' => false],
    );

    $user = User::factory()->create(['school_current_id' => $school->id]);
    $user->role()->associate($role)->save();
    $school->users()->attach($user->id, ['is_active' => true]);

    return $user;
}

function makeSchoolForRoomTests(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) ($counter + 500), 14, '0', STR_PAD_LEFT),
        'razao_social' => 'Escola Room '.$counter,
    ]);
}

function makeRoom(School $school, string $name = 'Sala 1'): Room
{
    return Room::factory()->create([
        'school_id' => $school->id,
        'name' => $name,
    ]);
}

// ---------------------------------------------------------------------------
// 1. Index returns 200 for gestor
// ---------------------------------------------------------------------------

it('index retorna 200 para gestor', function (): void {
    $school = makeSchoolForRoomTests();
    $user = roomGestorUser($school);

    app()->instance('tenant.school_id', $school->id);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('tenant.settings.rooms.index'))
        ->assertStatus(200);
});

// ---------------------------------------------------------------------------
// 2. Store creates room and redirects
// ---------------------------------------------------------------------------

it('store cria sala e redireciona', function (): void {
    $school = makeSchoolForRoomTests();
    $user = roomGestorUser($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->post(route('tenant.settings.rooms.store'), [
            'name' => 'Sala 101',
            'capacity' => 30,
            'is_external' => true,
        ])
        ->assertRedirect(route('tenant.settings.rooms.index'));

    $this->assertDatabaseHas('rooms', [
        'school_id' => $school->id,
        'name' => 'Sala 101',
        'capacity' => 30,
        'is_external' => true,
    ]);
});

// ---------------------------------------------------------------------------
// 3. Store with missing required fields returns 422
// ---------------------------------------------------------------------------

it('store retorna 422 quando nome está vazio', function (): void {
    $school = makeSchoolForRoomTests();
    $user = roomGestorUser($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('tenant.settings.rooms.store'), [
            'name' => '',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

// ---------------------------------------------------------------------------
// 4. Update updates room and redirects
// ---------------------------------------------------------------------------

it('update atualiza sala e redireciona', function (): void {
    $school = makeSchoolForRoomTests();
    $user = roomGestorUser($school);
    $room = makeRoom($school, 'Sala Antiga');

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->put(route('tenant.settings.rooms.update', $room), [
            'name' => 'Sala Atualizada',
            'capacity' => 25,
            'is_external' => true,
        ])
        ->assertRedirect(route('tenant.settings.rooms.index'));

    $this->assertDatabaseHas('rooms', [
        'id' => $room->id,
        'name' => 'Sala Atualizada',
        'capacity' => 25,
        'is_external' => true,
    ]);
});

// ---------------------------------------------------------------------------
// 5. Destroy soft-deletes room and redirects
// ---------------------------------------------------------------------------

it('destroy faz soft-delete da sala e redireciona', function (): void {
    $school = makeSchoolForRoomTests();
    $user = roomGestorUser($school);
    $room = makeRoom($school, 'Sala para Excluir');

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->delete(route('tenant.settings.rooms.destroy', $room))
        ->assertRedirect(route('tenant.settings.rooms.index'));

    $this->assertSoftDeleted('rooms', ['id' => $room->id]);
});

// ---------------------------------------------------------------------------
// 6. Gestor from another tenant cannot update room (404)
// ---------------------------------------------------------------------------

it('gestor de outro tenant não pode atualizar sala (404)', function (): void {
    $schoolA = makeSchoolForRoomTests();
    $schoolB = makeSchoolForRoomTests();

    $userB = roomGestorUser($schoolB);
    $room = makeRoom($schoolA, 'Sala Tenant A');

    app()->instance('tenant.school_id', $schoolB->id);

    $this->actingAs($userB)
        ->withHeader('Accept', 'application/json')
        ->put(route('tenant.settings.rooms.update', $room), [
            'name' => 'Tentativa',
        ])
        ->assertStatus(404);
});

// ---------------------------------------------------------------------------
// 7. Comercial can view rooms
// ---------------------------------------------------------------------------

it('comercial pode visualizar salas', function (): void {
    $school = makeSchoolForRoomTests();
    $user = roomComercialUser($school);

    app()->instance('tenant.school_id', $school->id);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('tenant.settings.rooms.index'))
        ->assertStatus(200);
});

// ---------------------------------------------------------------------------
// 8. Comercial cannot delete room (403)
// ---------------------------------------------------------------------------

it('comercial não pode excluir sala (403)', function (): void {
    $school = makeSchoolForRoomTests();
    $user = roomComercialUser($school);
    $room = makeRoom($school, 'Sala Comercial Test');

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->delete(route('tenant.settings.rooms.destroy', $room))
        ->assertStatus(403);
});
