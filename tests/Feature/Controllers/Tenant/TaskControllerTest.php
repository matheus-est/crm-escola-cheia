<?php

namespace Tests\Feature\Controllers\Tenant;

use App\Enums\OpportunityStatus;
use App\Enums\OutcomeActionType;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Opportunity;
use App\Models\Outcome;
use App\Models\Role;
use App\Models\School;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\CrmPermissionSeeder;
use Database\Seeders\RoleSeeder;

it('completes a task successfully, updating opportunity and creating a new task', function () {
    $this->seed(RoleSeeder::class);
    $this->seed(CrmPermissionSeeder::class);

    $school = School::factory()->create();
    $role = Role::firstOrCreate(['name' => 'Gestor']);
    $user = User::factory()->create([
        'role_id' => $role->id,
        'school_current_id' => $school->id,
    ]);
    $user->schools()->attach($school->id, ['is_active' => true]);

    $opportunity = Opportunity::factory()->create([
        'school_id' => $school->id,
        'status' => OpportunityStatus::Agendamento->value,
    ]);

    $task = Task::factory()->create([
        'school_id' => $school->id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::Agendamento->value,
        'status' => TaskStatus::Open->value,
    ]);

    $outcome = Outcome::create([
        'name' => 'Compareceu',
        'slug' => 'compareceu',
        'task_type' => TaskType::Agendamento->value,
    ]);

    $outcome->actions()->create([
        'action_type' => OutcomeActionType::MoveStatus->value,
        'payload' => ['status' => OpportunityStatus::Visita->value],
        'order' => 1,
    ]);

    $outcome->actions()->create([
        'action_type' => OutcomeActionType::CreateTask->value,
        'payload' => [
            'task_type' => TaskType::ProvavelMatricula->value,
            'delay_days' => 1,
        ],
        'order' => 2,
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('tenant.tasks.complete', ['task' => $task->uuid]), [
            'outcome_uuid' => $outcome->uuid,
        ]);

    $response->assertOk();

    $this->assertDatabaseHas('tasks', [
        'id' => $task->id,
        'status' => TaskStatus::Completed->value,
    ]);

    $this->assertDatabaseHas('opportunities', [
        'id' => $opportunity->id,
        'status' => OpportunityStatus::Visita->value,
    ]);

    $this->assertDatabaseHas('tasks', [
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::ProvavelMatricula->value,
        'status' => TaskStatus::Open->value,
        'school_id' => $school->id,
    ]);
});
