<?php

declare(strict_types=1);

use App\Models\EventType;
use App\Models\Role;
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

function makeSchoolForEventTypeTests(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) ($counter + 700), 14, '0', STR_PAD_LEFT),
        'legal_name' => 'Escola EventType '.$counter,
        'slug' => 'escola-eventtype-'.$counter,
    ]);
}

function makeEventTypeUser(School $school, string $roleName = 'Gestor'): User
{
    $role = Role::firstOrCreate(
        ['name' => $roleName],
        ['description' => $roleName, 'is_default' => false],
    );

    $user = User::factory()->create(['school_current_id' => $school->id]);
    $user->role()->associate($role)->save();
    $school->users()->attach($user->id, ['is_active' => true]);

    return $user;
}

function makeEventType(School $school, string $name = 'Palestra', bool $isSystem = false): EventType
{
    return EventType::withoutEventTypeScope()->create([
        'school_id' => $isSystem ? null : $school->id,
        'name' => $name,
        'is_active' => true,
        'is_system' => $isSystem,
    ]);
}

function makeSystemEventType(string $name = 'Palestra'): EventType
{
    return EventType::withoutEventTypeScope()->firstOrCreate(
        ['name' => $name, 'school_id' => null, 'is_system' => true],
        ['is_active' => true],
    );
}

// ---------------------------------------------------------------------------
// 1. Index returns paginated results
// ---------------------------------------------------------------------------

it('index retorna resultados paginados', function (): void {
    $school = makeSchoolForEventTypeTests();
    $user = makeEventTypeUser($school);

    app()->instance('tenant.school_id', $school->id);

    makeEventType($school, 'Workshop Próprio');

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('tenant.settings.event_types.index'))
        ->assertStatus(200);
});

// ---------------------------------------------------------------------------
// 2. Store creates event type with correct school_id and is_system = false
// ---------------------------------------------------------------------------

it('store cria tipo de evento com school_id correto e is_system false', function (): void {
    $school = makeSchoolForEventTypeTests();
    $user = makeEventTypeUser($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->post(route('tenant.settings.event_types.store'), [
            'name' => 'Feira Científica',
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('event_types', [
        'school_id' => $school->id,
        'name' => 'Feira Científica',
        'is_active' => true,
        'is_system' => false,
    ]);
});

// ---------------------------------------------------------------------------
// 3. Update changes name and is_active
// ---------------------------------------------------------------------------

it('update altera nome e is_active', function (): void {
    $school = makeSchoolForEventTypeTests();
    $user = makeEventTypeUser($school);
    $eventType = makeEventType($school, 'Antigo');

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->put(route('tenant.settings.event_types.update', $eventType), [
            'name' => 'Novo Nome',
            'is_active' => false,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('event_types', [
        'id' => $eventType->id,
        'name' => 'Novo Nome',
        'is_active' => false,
    ]);
});

// ---------------------------------------------------------------------------
// 4. toggleActive flips is_active — deactivate
// ---------------------------------------------------------------------------

it('toggleActive inativa um tipo de evento ativo', function (): void {
    $school = makeSchoolForEventTypeTests();
    $user = makeEventTypeUser($school);
    $eventType = makeEventType($school, 'Tipo Ativo');

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->post(route('tenant.settings.event_types.toggle_active', $eventType))
        ->assertRedirect();

    $this->assertDatabaseHas('event_types', [
        'id' => $eventType->id,
        'is_active' => false,
    ]);
});

// ---------------------------------------------------------------------------
// 5. toggleActive flips is_active — reactivate
// ---------------------------------------------------------------------------

it('toggleActive reativa um tipo de evento inativo', function (): void {
    $school = makeSchoolForEventTypeTests();
    $user = makeEventTypeUser($school);
    $eventType = EventType::withoutEventTypeScope()->create([
        'school_id' => $school->id,
        'name' => 'Tipo Inativo',
        'is_active' => false,
        'is_system' => false,
    ]);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->post(route('tenant.settings.event_types.toggle_active', $eventType))
        ->assertRedirect();

    $this->assertDatabaseHas('event_types', [
        'id' => $eventType->id,
        'is_active' => true,
    ]);
});

// ---------------------------------------------------------------------------
// 6. Duplicate name in same tenant returns 422
// ---------------------------------------------------------------------------

it('nome duplicado no mesmo tenant retorna 422', function (): void {
    $school = makeSchoolForEventTypeTests();
    $user = makeEventTypeUser($school);

    makeEventType($school, 'Workshop');

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('tenant.settings.event_types.store'), [
            'name' => 'Workshop',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

// ---------------------------------------------------------------------------
// 7. Forged school_id in payload is ignored
// ---------------------------------------------------------------------------

it('school_id forjado no payload é ignorado', function (): void {
    $schoolA = makeSchoolForEventTypeTests();
    $schoolB = makeSchoolForEventTypeTests();
    $user = makeEventTypeUser($schoolA);

    app()->instance('tenant.school_id', $schoolA->id);

    $this->actingAs($user)
        ->post(route('tenant.settings.event_types.store'), [
            'name' => 'Tipo Forjado',
            'school_id' => $schoolB->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('event_types', [
        'name' => 'Tipo Forjado',
        'school_id' => $schoolA->id,
    ]);

    $this->assertDatabaseMissing('event_types', [
        'name' => 'Tipo Forjado',
        'school_id' => $schoolB->id,
    ]);
});

// ---------------------------------------------------------------------------
// 8. Unauthorized role returns 403
// ---------------------------------------------------------------------------

it('perfil não autorizado retorna 403', function (): void {
    $school = makeSchoolForEventTypeTests();
    $user = makeEventTypeUser($school, 'Comercial');

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('tenant.settings.event_types.store'), [
            'name' => 'Tipo Comercial',
        ])
        ->assertStatus(403);
});

// ---------------------------------------------------------------------------
// 9. Tenant sees system records (is_system = true)
// ---------------------------------------------------------------------------

it('tenant vê registros globais is_system=true', function (): void {
    $school = makeSchoolForEventTypeTests();

    app()->instance('tenant.school_id', $school->id);

    makeSystemEventType('Palestra');
    makeSystemEventType('Workshop');

    $results = EventType::query()->get();

    expect($results->where('is_system', true)->count())->toBe(2);
});

// ---------------------------------------------------------------------------
// 10. Tenant does NOT see records from another tenant
// ---------------------------------------------------------------------------

it('tenant não vê registros de outro tenant', function (): void {
    $schoolA = makeSchoolForEventTypeTests();
    $schoolB = makeSchoolForEventTypeTests();

    makeEventType($schoolB, 'Tipo do Tenant B');

    app()->instance('tenant.school_id', $schoolA->id);

    $results = EventType::query()->get();

    expect($results->where('name', 'Tipo do Tenant B')->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// 11. destroy on is_system = true is blocked
// ---------------------------------------------------------------------------

it('destroy em registro is_system=true é bloqueado', function (): void {
    $school = makeSchoolForEventTypeTests();
    $user = makeEventTypeUser($school);
    $systemEventType = makeSystemEventType('Visita');

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->delete(route('tenant.settings.event_types.destroy', $systemEventType))
        ->assertRedirect();

    $this->assertDatabaseHas('event_types', [
        'id' => $systemEventType->id,
        'is_active' => true,
    ]);
});

// ---------------------------------------------------------------------------
// 12. toggleActive on is_system = true is blocked
// ---------------------------------------------------------------------------

it('toggleActive em registro is_system=true é bloqueado', function (): void {
    $school = makeSchoolForEventTypeTests();
    $user = makeEventTypeUser($school);
    $systemEventType = makeSystemEventType('Palestra');

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->post(route('tenant.settings.event_types.toggle_active', $systemEventType))
        ->assertRedirect();

    $this->assertDatabaseHas('event_types', [
        'id' => $systemEventType->id,
        'is_active' => true,
    ]);
});
