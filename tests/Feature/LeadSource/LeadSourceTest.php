<?php

declare(strict_types=1);

use App\Models\LeadSource;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\LeadSource\LeadSourceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('tenant.school_id');
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function leadSourceMasterUser(): User
{
    $role = Role::firstOrCreate(
        ['name' => 'Master'],
        ['description' => 'Master', 'is_default' => false],
    );

    $user = User::factory()->create();
    $user->role()->associate($role)->save();

    return $user;
}

function leadSourceGestorUser(School $school): User
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

function leadSourceComercialUser(School $school): User
{
    $role = Role::firstOrCreate(
        ['name' => 'Comercial'],
        ['description' => 'Comercial', 'is_default' => false],
    );

    $user = User::factory()->create();
    $user->role()->associate($role)->save();
    $school->users()->attach($user->id, ['is_active' => true]);

    return $user;
}

function makeSchoolForLeadSourceTests(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) ($counter + 900), 14, '0', STR_PAD_LEFT),
        'razao_social' => 'Escola LeadSource '.$counter,
    ]);
}

function makeSystemLeadSource(): LeadSource
{
    return LeadSource::withoutLeadSourceScope()->create([
        'school_id' => null,
        'nome' => 'Origem Sistema',
        'is_system' => true,
        'is_active' => true,
    ]);
}

function makeTenantLeadSource(School $school): LeadSource
{
    return LeadSource::withoutLeadSourceScope()->create([
        'school_id' => $school->id,
        'nome' => 'Origem Tenant',
        'is_system' => false,
        'is_active' => true,
    ]);
}

// ---------------------------------------------------------------------------
// Testes de escopo (visibilidade)
// ---------------------------------------------------------------------------

it('origem de sistema é visível em qualquer tenant', function (): void {
    $school = makeSchoolForLeadSourceTests();
    $systemSource = makeSystemLeadSource();

    app()->instance('tenant.school_id', $school->id);

    $found = LeadSource::query()->find($systemSource->id);

    expect($found)->not->toBeNull();
    expect($found->id)->toBe($systemSource->id);
});

it('origem do tenant é visível apenas no próprio tenant', function (): void {
    $schoolA = makeSchoolForLeadSourceTests();
    $schoolB = makeSchoolForLeadSourceTests();

    $tenantSource = makeTenantLeadSource($schoolA);

    // Visível no tenant correto
    app()->instance('tenant.school_id', $schoolA->id);
    $foundInA = LeadSource::query()->find($tenantSource->id);
    expect($foundInA)->not->toBeNull();

    app()->forgetInstance('tenant.school_id');

    // Não visível em outro tenant
    app()->instance('tenant.school_id', $schoolB->id);
    $foundInB = LeadSource::query()->find($tenantSource->id);
    expect($foundInB)->toBeNull();
});

it('list() retorna origens do tenant e origens de sistema juntas', function (): void {
    $school = makeSchoolForLeadSourceTests();

    $systemSource = makeSystemLeadSource();
    $tenantSource = makeTenantLeadSource($school);

    app()->instance('tenant.school_id', $school->id);

    $service = app(LeadSourceService::class);
    $paginator = $service->list($school);

    $ids = $paginator->pluck('id')->all();

    expect($ids)->toContain($systemSource->id);
    expect($ids)->toContain($tenantSource->id);
});

// ---------------------------------------------------------------------------
// GET index
// ---------------------------------------------------------------------------

it('GET index retorna 200 para usuário Master autenticado', function (): void {
    $user = leadSourceMasterUser();
    $school = makeSchoolForLeadSourceTests();

    app()->instance('tenant.school_id', $school->id);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('tenant.lead_sources.index', $school))
        ->assertStatus(200);
});

it('GET index retorna 302 para usuário não autenticado', function (): void {
    $school = makeSchoolForLeadSourceTests();

    $this->get(route('tenant.lead_sources.index', $school))
        ->assertStatus(302);
});

// ---------------------------------------------------------------------------
// CRUD — store
// ---------------------------------------------------------------------------

it('POST store cria uma LeadSource e redireciona', function (): void {
    $user = leadSourceMasterUser();
    $school = makeSchoolForLeadSourceTests();

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->post(route('tenant.lead_sources.store', $school), [
            'nome' => 'Instagram',
            'is_active' => true,
        ])
        ->assertRedirect(route('tenant.lead_sources.index', $school));

    $this->assertDatabaseHas('lead_sources', [
        'school_id' => $school->id,
        'nome' => 'Instagram',
        'is_system' => false,
    ]);
});

it('POST store retorna 422 quando nome está vazio', function (): void {
    $user = leadSourceMasterUser();
    $school = makeSchoolForLeadSourceTests();

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('tenant.lead_sources.store', $school), [
            'nome' => '',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['nome']);
});

it('POST store retorna 403 para usuário Comercial', function (): void {
    $school = makeSchoolForLeadSourceTests();
    $user = leadSourceComercialUser($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->post(route('tenant.lead_sources.store', $school), [
            'nome' => 'Facebook',
            'is_active' => true,
        ])
        ->assertStatus(403);
});

// ---------------------------------------------------------------------------
// CRUD — update
// ---------------------------------------------------------------------------

it('PUT update atualiza uma LeadSource e redireciona', function (): void {
    $user = leadSourceMasterUser();
    $school = makeSchoolForLeadSourceTests();
    $leadSource = makeTenantLeadSource($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->put(route('tenant.lead_sources.update', [$school, $leadSource]), [
            'nome' => 'Origem Atualizada',
            'is_active' => false,
        ])
        ->assertRedirect(route('tenant.lead_sources.index', $school));

    $this->assertDatabaseHas('lead_sources', [
        'id' => $leadSource->id,
        'nome' => 'Origem Atualizada',
        'is_active' => false,
    ]);
});

it('PUT update em origem de sistema retorna 403', function (): void {
    $user = leadSourceMasterUser();
    $school = makeSchoolForLeadSourceTests();
    $systemSource = makeSystemLeadSource();

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->put(route('tenant.lead_sources.update', [$school, $systemSource]), [
            'nome' => 'Tentativa',
            'is_active' => true,
        ])
        ->assertStatus(403);
});

// ---------------------------------------------------------------------------
// CRUD — destroy
// ---------------------------------------------------------------------------

it('DELETE destroy remove uma LeadSource e redireciona', function (): void {
    $user = leadSourceMasterUser();
    $school = makeSchoolForLeadSourceTests();
    $leadSource = makeTenantLeadSource($school);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->delete(route('tenant.lead_sources.destroy', [$school, $leadSource]))
        ->assertRedirect(route('tenant.lead_sources.index', $school));

    $this->assertDatabaseMissing('lead_sources', [
        'id' => $leadSource->id,
    ]);
});

it('DELETE destroy em origem de sistema retorna 403', function (): void {
    $user = leadSourceMasterUser();
    $school = makeSchoolForLeadSourceTests();
    $systemSource = makeSystemLeadSource();

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($user)
        ->withHeader('Accept', 'application/json')
        ->delete(route('tenant.lead_sources.destroy', [$school, $systemSource]))
        ->assertStatus(403);

    $this->assertDatabaseHas('lead_sources', [
        'id' => $systemSource->id,
    ]);
});
