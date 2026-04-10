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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('tenant.school_id');
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function showMakeRole(string $name): Role
{
    return Role::firstOrCreate(
        ['name' => $name],
        ['description' => $name, 'is_default' => false],
    );
}

function showMakeSchool(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) ($counter + 80000), 14, '0', STR_PAD_LEFT),
        'legal_name' => "Escola Show {$counter}",
        'slug' => "escola-show-{$counter}",
    ]);
}

function showMakeUser(string $roleName, ?School $school = null): User
{
    $role = showMakeRole($roleName);
    $user = User::factory()->create();
    $user->role()->associate($role)->save();

    if ($school !== null) {
        $school->users()->attach($user->id, ['is_active' => true]);
    }

    return $user;
}

function showMakeOpportunity(School $school): Opportunity
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

function showMakeTask(Opportunity $opportunity): Task
{
    return Task::withoutTenantScope()->create([
        'school_id' => $opportunity->school_id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->addHour(),
    ]);
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

it('GET show retorna 200 para usuário autenticado da escola', function (): void {
    $school = showMakeSchool();
    $user = showMakeUser('Gestor', $school);
    $opportunity = showMakeOpportunity($school);

    app()->instance('tenant.school_id', $school->id);

    $response = $this->withoutVite()
        ->actingAs($user)
        ->get("/tenant/opportunities/{$opportunity->uuid}");

    $response->assertOk();
});

it('GET show retorna 302 para usuário não autenticado', function (): void {
    $school = showMakeSchool();
    $opportunity = showMakeOpportunity($school);

    app()->instance('tenant.school_id', $school->id);

    $this->get("/tenant/opportunities/{$opportunity->uuid}")
        ->assertRedirect();
});

it('GET show retorna 404 para oportunidade de outra escola (tenant scope)', function (): void {
    $school1 = showMakeSchool();
    $school2 = showMakeSchool();
    $user = showMakeUser('Gestor', $school1);
    $opportunity = showMakeOpportunity($school2);

    app()->instance('tenant.school_id', $school1->id);

    // Route model binding via BelongsToTenant scope filters out other-school records → 404
    $this->withoutVite()
        ->actingAs($user)
        ->get("/tenant/opportunities/{$opportunity->uuid}")
        ->assertNotFound();
});

it('GET show renderiza página opportunities/Show via Inertia', function (): void {
    $school = showMakeSchool();
    $user = showMakeUser('Gestor', $school);
    $opportunity = showMakeOpportunity($school);

    app()->instance('tenant.school_id', $school->id);

    $this->withoutVite()
        ->actingAs($user)
        ->get("/tenant/opportunities/{$opportunity->uuid}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('opportunities/Show', false)
        );
});

it('GET show inclui tasks e outcomes nas props Inertia', function (): void {
    $school = showMakeSchool();
    $user = showMakeUser('Gestor', $school);
    $opportunity = showMakeOpportunity($school);
    showMakeTask($opportunity);

    app()->instance('tenant.school_id', $school->id);

    $this->withoutVite()
        ->actingAs($user)
        ->get("/tenant/opportunities/{$opportunity->uuid}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('opportunities/Show', false)
            ->has('tasks')
            ->has('outcomes')
            ->has('users')
            ->has('opportunity')
        );
});
