<?php

declare(strict_types=1);

use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Event;
use App\Models\Grade;
use App\Models\Opportunity;
use App\Models\Role;
use App\Models\Room;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\Segment;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('tenant.school_id');
});

function eventGestorUser(School $school): User
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

function eventMasterUser(School $school): User
{
    $role = Role::firstOrCreate(
        ['name' => 'Master'],
        ['description' => 'Master', 'is_default' => false],
    );

    $user = User::factory()->create();
    $user->role()->associate($role)->save();
    $school->users()->attach($user->id, ['is_active' => true]);

    return $user;
}

function makeSchoolForEventTests(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) ($counter + 9000), 14, '0', STR_PAD_LEFT),
        'legal_name' => 'Escola Evento '.$counter,
    ]);
}

function makeEventForSchool(School $school, array $overrides = []): Event
{
    return Event::factory()->create(array_merge([
        'school_id' => $school->id,
        'title' => 'Evento Teste',
        'event_date' => now()->addDays(7),
        'has_no_date' => false,
    ], $overrides));
}

function makeOpportunityForEvent(School $school): Opportunity
{
    $segment = Segment::firstOrCreate(
        ['name' => 'Ensino Fundamental'],
        ['name' => 'Ensino Fundamental'],
    );

    $grade = Grade::withoutTenantScope()->firstOrCreate(
        ['school_id' => $school->id, 'name' => '1º Ano Evento'],
        ['segment_id' => $segment->id],
    );

    $schoolYear = SchoolYear::withoutTenantScope()->firstOrCreate(
        ['school_id' => $school->id, 'name' => '2025-Evento'],
        [
            'start' => '2025-01-01',
            'end' => '2025-12-31',
            'status' => 'planejamento',
        ],
    );

    return Opportunity::withoutTenantScope()->create([
        'school_id' => $school->id,
        'grade_id' => $grade->id,
        'school_year_id' => $schoolYear->id,
        'status' => 'cadastro_inicial',
    ]);
}

function makeRoomForEvent(School $school, string $name = 'Sala 1'): Room
{
    return Room::factory()->create([
        'school_id' => $school->id,
        'name' => $name,
    ]);
}

it('GET index retorna 200 para gestor autenticado', function (): void {
    $school = makeSchoolForEventTests();
    $user = eventGestorUser($school);

    app()->instance('tenant.school_id', $school->id);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('tenant.events.index'))
        ->assertStatus(200);
});

it('GET create retorna 200 para gestor autenticado', function (): void {
    $school = makeSchoolForEventTests();
    $user = eventGestorUser($school);

    app()->instance('tenant.school_id', $school->id);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('tenant.events.create'))
        ->assertStatus(200);
});

it('POST store cria um evento e redireciona', function (): void {
    $school = makeSchoolForEventTests();
    $user = eventGestorUser($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->post(route('tenant.events.store'), [
            'title' => 'Feira de Ciências',
            'event_date' => now()->addDays(10)->format('Y-m-d H:i:s'),
        ])
        ->assertRedirect(route('tenant.events.index'));

    $this->assertDatabaseHas('events', [
        'school_id' => $school->id,
        'title' => 'Feira de Ciências',
    ]);
});

it('POST store com campos obrigatorios ausentes retorna 422', function (): void {
    $school = makeSchoolForEventTests();
    $user = eventGestorUser($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('tenant.events.store'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'event_date']);
});

it('GET edit retorna 200 para gestor autenticado', function (): void {
    $school = makeSchoolForEventTests();
    $user = eventGestorUser($school);
    $event = makeEventForSchool($school);

    app()->instance('tenant.school_id', $school->id);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('tenant.events.edit', ['event' => $event->uuid]))
        ->assertStatus(200);
});

it('PUT update atualiza evento e redireciona', function (): void {
    $school = makeSchoolForEventTests();
    $user = eventGestorUser($school);
    $event = makeEventForSchool($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->put(route('tenant.events.update', ['event' => $event->uuid]), [
            'title' => 'Evento Atualizado',
            'event_date' => now()->addDays(14)->format('Y-m-d H:i:s'),
        ])
        ->assertRedirect(route('tenant.events.index'));

    $this->assertDatabaseHas('events', [
        'id' => $event->id,
        'title' => 'Evento Atualizado',
    ]);
});

it('PUT update aceita event_date no passado', function (): void {
    $school = makeSchoolForEventTests();
    $user = eventGestorUser($school);
    $event = makeEventForSchool($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->put(route('tenant.events.update', ['event' => $event->uuid]), [
            'title' => 'Evento Passado',
            'event_date' => now()->subDays(3)->format('Y-m-d H:i:s'),
        ])
        ->assertRedirect(route('tenant.events.index'));
});

it('DELETE destroy faz soft delete e redireciona', function (): void {
    $school = makeSchoolForEventTests();
    $user = eventMasterUser($school);
    $event = makeEventForSchool($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->delete(route('tenant.events.destroy', ['event' => $event->uuid]))
        ->assertRedirect(route('tenant.events.index'));

    $this->assertSoftDeleted('events', ['id' => $event->id]);
});

it('POST attachOpportunity cria task tipo evento e retorna task_uuid', function (): void {
    $school = makeSchoolForEventTests();
    $user = eventGestorUser($school);
    $event = makeEventForSchool($school);
    $opportunity = makeOpportunityForEvent($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('tenant.events.opportunities.attach', ['event' => $event->uuid]), [
            'opportunity_uuid' => $opportunity->uuid,
        ])
        ->assertStatus(200)
        ->assertJsonStructure(['task_uuid']);

    $this->assertDatabaseHas('event_opportunity', [
        'event_id' => $event->id,
        'opportunity_id' => $opportunity->id,
    ]);

    $this->assertDatabaseHas('tasks', [
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::Evento->value,
    ]);
});

it('POST attachOpportunity retorna 422 quando oportunidade ja vinculada', function (): void {
    $school = makeSchoolForEventTests();
    $user = eventGestorUser($school);
    $event = makeEventForSchool($school);
    $opportunity = makeOpportunityForEvent($school);

    // Manually attach without task to isolate the "already attached" check
    $event->opportunities()->attach($opportunity->id);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('tenant.events.opportunities.attach', ['event' => $event->uuid]), [
            'opportunity_uuid' => $opportunity->uuid,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['opportunity_uuid']);
});

it('POST attachOpportunity retorna 422 quando oportunidade ja possui tarefa aberta', function (): void {
    $school = makeSchoolForEventTests();
    $user = eventGestorUser($school);
    $event = makeEventForSchool($school);
    $opportunity = makeOpportunityForEvent($school);

    // Create an open task on the opportunity
    Task::withoutTenantScope()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->addHour(),
    ]);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('tenant.events.opportunities.attach', ['event' => $event->uuid]), [
            'opportunity_uuid' => $opportunity->uuid,
        ])
        ->assertStatus(422);
});

it('DELETE detachOpportunity remove vinculo mas preserva task', function (): void {
    $school = makeSchoolForEventTests();
    $user = eventGestorUser($school);
    $event = makeEventForSchool($school);
    $opportunity = makeOpportunityForEvent($school);

    // Manually create pivot and task to avoid multi-request session issues
    $event->opportunities()->attach($opportunity->id);
    Task::withoutTenantScope()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::Evento->value,
        'status' => TaskStatus::Open->value,
        'due_at' => $event->event_date,
    ]);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->delete(route('tenant.events.opportunities.detach', [
            'event' => $event->uuid,
            'opportunity' => $opportunity->uuid,
        ]))
        ->assertRedirect(route('tenant.events.edit', ['event' => $event->uuid]));

    $this->assertDatabaseMissing('event_opportunity', [
        'event_id' => $event->id,
        'opportunity_id' => $opportunity->id,
    ]);

    $this->assertDatabaseHas('tasks', [
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::Evento->value,
    ]);
});

it('Gestor de outro tenant nao consegue acessar evento', function (): void {
    $schoolA = makeSchoolForEventTests();
    $schoolB = makeSchoolForEventTests();

    $user = eventGestorUser($schoolB);
    $event = makeEventForSchool($schoolA);

    app()->instance('tenant.school_id', $schoolB->id);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('tenant.events.edit', ['event' => $event->uuid]))
        ->assertStatus(404);
});

it('GET available retorna apenas eventos aptos', function (): void {
    $school = makeSchoolForEventTests();
    $user = eventGestorUser($school);

    // Event at capacity (max_capacity=1, 1 opportunity attached)
    $eventFull = makeEventForSchool($school, [
        'title' => 'Evento Lotado',
        'max_capacity' => 1,
        'event_date' => now()->addDays(5),
    ]);
    $opportunity = makeOpportunityForEvent($school);
    $eventFull->opportunities()->attach($opportunity->id);

    // Available event with capacity
    makeEventForSchool($school, [
        'title' => 'Evento Disponivel',
        'event_date' => now()->addDays(8),
        'max_capacity' => 10,
    ]);

    // Past event (not available)
    makeEventForSchool($school, [
        'title' => 'Evento Passado',
        'event_date' => now()->subDay(),
    ]);

    app()->instance('tenant.school_id', $school->id);

    $response = $this->actingAs($user)
        ->get(route('tenant.events.available'))
        ->assertStatus(200);

    $data = $response->json();
    $titles = collect($data)->pluck('title')->toArray();

    expect($titles)->toContain('Evento Disponivel');
    expect($titles)->not->toContain('Evento Passado');
    expect($titles)->not->toContain('Evento Lotado');
});

// ---------------------------------------------------------------------------
// Tests: room_uuids pivot, has_no_date, grade_uuid
// ---------------------------------------------------------------------------

it('POST store com room_uuids cria registros na tabela event_room', function (): void {
    $school = makeSchoolForEventTests();
    $user = eventGestorUser($school);
    $room1 = makeRoomForEvent($school, 'Sala A');
    $room2 = makeRoomForEvent($school, 'Sala B');

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->post(route('tenant.events.store'), [
            'title' => 'Evento com Salas',
            'event_date' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'room_uuids' => [$room1->uuid, $room2->uuid],
        ])
        ->assertRedirect(route('tenant.events.index'));

    $event = Event::withoutTenantScope()->where('title', 'Evento com Salas')->firstOrFail();

    $this->assertDatabaseHas('event_room', ['event_id' => $event->id, 'room_id' => $room1->id]);
    $this->assertDatabaseHas('event_room', ['event_id' => $event->id, 'room_id' => $room2->id]);
});

it('PUT update com novos room_uuids desvincula antigos e vincula novos', function (): void {
    $school = makeSchoolForEventTests();
    $user = eventGestorUser($school);
    $roomOld = makeRoomForEvent($school, 'Sala Antiga');
    $roomNew = makeRoomForEvent($school, 'Sala Nova');
    $event = makeEventForSchool($school);
    $event->rooms()->attach($roomOld->id);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->put(route('tenant.events.update', ['event' => $event->uuid]), [
            'title' => 'Evento Atualizado',
            'event_date' => now()->addDays(14)->format('Y-m-d H:i:s'),
            'room_uuids' => [$roomNew->uuid],
        ])
        ->assertRedirect(route('tenant.events.index'));

    $this->assertDatabaseMissing('event_room', ['event_id' => $event->id, 'room_id' => $roomOld->id]);
    $this->assertDatabaseHas('event_room', ['event_id' => $event->id, 'room_id' => $roomNew->id]);
});

it('POST store com has_no_date=true e sem event_date é aceito', function (): void {
    $school = makeSchoolForEventTests();
    $user = eventGestorUser($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->post(route('tenant.events.store'), [
            'title' => 'Evento Sem Data',
            'has_no_date' => true,
        ])
        ->assertRedirect(route('tenant.events.index'));

    $this->assertDatabaseHas('events', [
        'school_id' => $school->id,
        'title' => 'Evento Sem Data',
        'has_no_date' => true,
    ]);
});

it('POST store com has_no_date=false e sem event_date retorna 422', function (): void {
    $school = makeSchoolForEventTests();
    $user = eventGestorUser($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('tenant.events.store'), [
            'title' => 'Evento sem data invalido',
            'has_no_date' => false,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['event_date']);
});

it('POST store com grade_uuid invalido retorna 422', function (): void {
    $school = makeSchoolForEventTests();
    $user = eventGestorUser($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('tenant.events.store'), [
            'title' => 'Evento Grade Invalida',
            'event_date' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'grade_uuid' => 'not-a-real-uuid',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['grade_uuid']);
});

it('POST store com grade_uuid valido armazena corretamente', function (): void {
    $school = makeSchoolForEventTests();
    $user = eventGestorUser($school);

    $segment = Segment::firstOrCreate(
        ['name' => 'Ensino Fundamental'],
        ['name' => 'Ensino Fundamental'],
    );

    $grade = Grade::withoutTenantScope()->create([
        'school_id' => $school->id,
        'segment_id' => $segment->id,
        'name' => 'Grade do Evento',
        'order' => 1,
    ]);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->post(route('tenant.events.store'), [
            'title' => 'Evento com Grade',
            'event_date' => now()->addDays(5)->format('Y-m-d H:i:s'),
            'grade_uuid' => $grade->uuid,
        ])
        ->assertRedirect(route('tenant.events.index'));

    $this->assertDatabaseHas('events', [
        'school_id' => $school->id,
        'title' => 'Evento com Grade',
        'grade_id' => $grade->id,
    ]);
});
