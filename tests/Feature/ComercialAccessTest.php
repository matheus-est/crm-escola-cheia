<?php

declare(strict_types=1);

use App\Enums\OpportunityStatus;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Grade;
use App\Models\Opportunity;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolYear;
use App\Models\Segment;
use App\Models\Student;
use App\Models\Task;
use App\Models\User;
use App\Services\Opportunity\OpportunityService;
use App\Services\Task\TaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

afterEach(function (): void {
    app()->forgetInstance('tenant.school_id');
});

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function catMakeSchool(): School
{
    static $counter = 0;
    $counter++;

    return School::create([
        'cnpj' => str_pad((string) ($counter + 70000), 14, '0', STR_PAD_LEFT),
        'legal_name' => "Escola CAT {$counter}",
        'slug' => "escola-cat-{$counter}",
    ]);
}

function catMakeUser(School $school, string $roleName): User
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

function catMakeGrade(School $school): Grade
{
    $segment = Segment::firstOrCreate(
        ['name' => 'Ensino Fundamental CAT'],
        ['name' => 'Ensino Fundamental CAT'],
    );

    return Grade::withoutTenantScope()->firstOrCreate([
        'school_id' => $school->id,
        'segment_id' => $segment->id,
        'name' => '1º Ano CAT',
    ]);
}

function catMakeSchoolYear(School $school): SchoolYear
{
    return SchoolYear::withoutTenantScope()->firstOrCreate(
        ['school_id' => $school->id, 'name' => '2025-CAT'],
        [
            'start' => '2025-01-01',
            'end' => '2025-12-31',
            'status' => 'planejamento',
        ]
    );
}

function catMakeOpportunity(School $school, ?int $responsibleUserId = null): Opportunity
{
    $grade = catMakeGrade($school);
    $schoolYear = catMakeSchoolYear($school);

    return Opportunity::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'school_id' => $school->id,
        'student_id' => Student::factory()->create()->id,
        'grade_id' => $grade->id,
        'school_year_id' => $schoolYear->id,
        'status' => OpportunityStatus::CadastroInicial->value,
        'renitente_count' => 0,
        'responsible_user_id' => $responsibleUserId,
    ]);
}

function catMakeTask(Opportunity $opportunity, ?int $assignedUserId = null): Task
{
    return Task::withoutGlobalScopes()->create([
        'uuid' => (string) Str::uuid(),
        'school_id' => $opportunity->school_id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::RetornoLigacao->value,
        'status' => TaskStatus::Open->value,
        'due_at' => now()->addHour(),
        'assigned_user_id' => $assignedUserId,
    ]);
}

// ---------------------------------------------------------------------------
// Test 1: Comercial lists only own opportunities (responsible_user_id)
// ---------------------------------------------------------------------------

it('comercial lists only opportunities where they are responsible_user', function (): void {
    $school = catMakeSchool();
    $comercial = catMakeUser($school, 'Comercial');
    $other = catMakeUser($school, 'Comercial');

    $ownOpportunity = catMakeOpportunity($school, $comercial->id);
    catMakeOpportunity($school, $other->id);   // belongs to another user
    catMakeOpportunity($school, null);          // no responsible user

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($comercial);

    $service = app(OpportunityService::class);
    $result = $service->list();

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->id)->toBe($ownOpportunity->id);
});

// ---------------------------------------------------------------------------
// Test 2: Comercial receives 403 when accessing another user's opportunity show
// ---------------------------------------------------------------------------

it('comercial receives 403 when viewing an opportunity they are not responsible for', function (): void {
    $school = catMakeSchool();
    $comercial = catMakeUser($school, 'Comercial');
    $other = catMakeUser($school, 'Comercial');

    $otherOpportunity = catMakeOpportunity($school, $other->id);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($comercial)
        ->getJson(route('tenant.opportunities.show', ['opportunity' => $otherOpportunity->uuid]))
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Test 3: Comercial lists only tasks where they are assigned_user
// ---------------------------------------------------------------------------

it('comercial lists only tasks where they are assigned_user', function (): void {
    $school = catMakeSchool();
    $comercial = catMakeUser($school, 'Comercial');
    $other = catMakeUser($school, 'Comercial');

    $opportunity1 = catMakeOpportunity($school, $comercial->id);
    $opportunity2 = catMakeOpportunity($school, $other->id);
    $opportunity3 = catMakeOpportunity($school, null);

    $ownTask = catMakeTask($opportunity1, $comercial->id);
    catMakeTask($opportunity2, $other->id);  // another user's task
    catMakeTask($opportunity3, null);        // unassigned task

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($comercial);

    $service = app(TaskService::class);
    $result = $service->list();

    expect($result->total())->toBe(1)
        ->and($result->items()[0]->id)->toBe($ownTask->id);
});

// ---------------------------------------------------------------------------
// Test 4: Admin lists all opportunities without restriction
// ---------------------------------------------------------------------------

it('admin lists all opportunities without restriction', function (): void {
    $school = catMakeSchool();
    $admin = catMakeUser($school, 'Admin');
    $comercial = catMakeUser($school, 'Comercial');

    catMakeOpportunity($school, $comercial->id);
    catMakeOpportunity($school, null);
    catMakeOpportunity($school, $admin->id);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($admin);

    $service = app(OpportunityService::class);
    $result = $service->list();

    expect($result->total())->toBe(3);
});

// ---------------------------------------------------------------------------
// Test 5: Gestor lists all opportunities without restriction
// ---------------------------------------------------------------------------

it('gestor lists all opportunities without restriction', function (): void {
    $school = catMakeSchool();
    $gestor = catMakeUser($school, 'Gestor');
    $comercial = catMakeUser($school, 'Comercial');

    catMakeOpportunity($school, $comercial->id);
    catMakeOpportunity($school, null);
    catMakeOpportunity($school, $gestor->id);

    app()->instance('tenant.school_id', $school->id);

    $this->actingAs($gestor);

    $service = app(OpportunityService::class);
    $result = $service->list();

    expect($result->total())->toBe(3);
});

// ---------------------------------------------------------------------------
// Test 6: Comercial user sees only own tasks via controller index
// ---------------------------------------------------------------------------

it('comercial user sees only own tasks via controller index', function (): void {
    $school = catMakeSchool();
    $comercial = catMakeUser($school, 'Comercial');
    $other = catMakeUser($school, 'Gestor');

    $ownOpportunity = catMakeOpportunity($school, $comercial->id);
    $otherOpportunity = catMakeOpportunity($school, $other->id);

    $ownTask = catMakeTask($ownOpportunity, $comercial->id);
    catMakeTask($otherOpportunity, $other->id);

    app()->instance('tenant.school_id', $school->id);

    $this->withoutVite()
        ->actingAs($comercial)
        ->get(route('tenant.tasks.index'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('tasks/Index', false)
            ->has('tasks.data', 1)
            ->where('tasks.data.0.uuid', $ownTask->uuid)
        );
});
