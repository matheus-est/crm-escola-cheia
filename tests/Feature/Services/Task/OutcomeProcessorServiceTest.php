<?php

namespace Tests\Feature\Services\Task;

use App\Enums\OpportunityStatus;
use App\Enums\OutcomeActionType;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Opportunity;
use App\Models\Outcome;
use App\Models\Task;
use App\Services\Task\OutcomeProcessorService;
use Illuminate\Validation\ValidationException;

it('moves to Visita and creates Provável Matrícula task on compareceu_agendamento', function () {
    $opportunity = Opportunity::factory()->create([
        'status' => OpportunityStatus::Agendamento->value,
    ]);

    $task = Task::factory()->create([
        'school_id' => $opportunity->school_id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::Agendamento->value,
    ]);

    $outcome = Outcome::create([
        'name' => 'Compareceu',
        'slug' => 'compareceu_agendamento',
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

    $service = app(OutcomeProcessorService::class);
    $service->process($task, $outcome);

    $this->assertDatabaseHas('opportunities', [
        'id' => $opportunity->id,
        'status' => OpportunityStatus::Visita->value,
    ]);

    $this->assertDatabaseHas('tasks', [
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::ProvavelMatricula->value,
        'status' => TaskStatus::Open->value,
    ]);
});

it('creates Reagendamento task with due_date +2 days on nao_compareceu_agendamento', function () {
    $opportunity = Opportunity::factory()->create([
        'status' => OpportunityStatus::Agendamento->value,
    ]);

    $task = Task::factory()->create([
        'school_id' => $opportunity->school_id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::Agendamento->value,
    ]);

    $outcome = Outcome::create([
        'name' => 'Não Compareceu',
        'slug' => 'nao_compareceu_agendamento',
        'task_type' => TaskType::Agendamento->value,
    ]);

    $outcome->actions()->create([
        'action_type' => OutcomeActionType::CreateTask->value,
        'payload' => [
            'task_type' => TaskType::Reagendamento->value,
            'delay_days' => 2,
        ],
        'order' => 1,
    ]);

    $service = app(OutcomeProcessorService::class);
    $service->process($task, $outcome);

    $taskCreated = Task::where('opportunity_id', $opportunity->id)
        ->where('type', TaskType::Reagendamento->value)
        ->first();

    expect($taskCreated)->not->toBeNull();
    // Verify due_at (delay_days = 2) ignoring time diff limits roughly
    expect($taskCreated->due_at->toDateString())->toBe(now()->addDays(2)->toDateString());
});

it('moves to Matricula on pre_matricula', function () {
    $opportunity = Opportunity::factory()->create([
        'status' => OpportunityStatus::Visita->value,
    ]);

    $task = Task::factory()->create([
        'school_id' => $opportunity->school_id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::ProvavelMatricula->value,
    ]);

    $outcome = Outcome::create([
        'name' => 'Pré-Matrícula',
        'slug' => 'pre_matricula',
        'task_type' => TaskType::ProvavelMatricula->value,
    ]);

    $outcome->actions()->create([
        'action_type' => OutcomeActionType::MoveStatus->value,
        'payload' => ['status' => OpportunityStatus::Matricula->value],
        'order' => 1,
    ]);

    $service = app(OutcomeProcessorService::class);
    $service->process($task, $outcome);

    $this->assertDatabaseHas('opportunities', [
        'id' => $opportunity->id,
        'status' => OpportunityStatus::Matricula->value,
    ]);
});

it('throws ValidationException when refusing without category', function () {
    $opportunity = Opportunity::factory()->create([
        'status' => OpportunityStatus::Visita->value,
    ]);

    $task = Task::factory()->create([
        'school_id' => $opportunity->school_id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::ProvavelMatricula->value,
    ]);

    $outcome = Outcome::create([
        'name' => 'Recusa',
        'slug' => 'recusa_visita',
        'task_type' => TaskType::ProvavelMatricula->value,
    ]);

    $outcome->actions()->create([
        'action_type' => OutcomeActionType::MoveStatus->value,
        'payload' => ['status' => OpportunityStatus::Recusado->value],
        'order' => 1,
    ]);

    $service = app(OutcomeProcessorService::class);

    expect(fn () => $service->process($task, $outcome))
        ->toThrow(ValidationException::class);
});

it('rolls back transaction if an action fails', function () {
    $opportunity = Opportunity::factory()->create([
        'status' => OpportunityStatus::Visita->value,
    ]);

    $task = Task::factory()->create([
        'school_id' => $opportunity->school_id,
        'opportunity_id' => $opportunity->id,
        'type' => TaskType::ProvavelMatricula->value,
    ]);

    $outcome = Outcome::create([
        'name' => 'Recusa Falha',
        'slug' => 'recusa_falha',
        'task_type' => TaskType::ProvavelMatricula->value,
    ]);

    // First action works
    $outcome->actions()->create([
        'action_type' => OutcomeActionType::MoveStatus->value,
        'payload' => ['status' => OpportunityStatus::Agendamento->value],
        'order' => 1,
    ]);

    // Second action fails (recused without refusal_category)
    $outcome->actions()->create([
        'action_type' => OutcomeActionType::MoveStatus->value,
        'payload' => ['status' => OpportunityStatus::Recusado->value],
        'order' => 2,
    ]);

    $service = app(OutcomeProcessorService::class);

    try {
        $service->process($task, $outcome);
    } catch (ValidationException $e) {
        // Expected
    }

    // Asserts that the first action was rolled back, so it should still be Visita
    $this->assertDatabaseHas('opportunities', [
        'id' => $opportunity->id,
        'status' => OpportunityStatus::Visita->value,
    ]);
});

it('returns open_window correctly for outcomes that open modal', function () {
    $opportunity = Opportunity::factory()->create();
    $task = Task::factory()->create([
        'opportunity_id' => $opportunity->id,
        'school_id' => $opportunity->school_id,
    ]);

    $outcome = Outcome::create([
        'name' => 'Agendar Nova',
        'slug' => 'agendar',
        'task_type' => TaskType::Agendamento->value,
    ]);

    $outcome->actions()->create([
        'action_type' => OutcomeActionType::OpenWindow->value,
        'payload' => ['window' => 'CreateTaskModal'],
        'order' => 1,
    ]);

    $service = app(OutcomeProcessorService::class);

    $result = $service->process($task, $outcome);

    expect($result)->toHaveKey('open_window', 'CreateTaskModal');
});

it('throws DomainException when trying to move a terminal opportunity to another status', function () {
    $opportunity = Opportunity::factory()->create([
        'status' => OpportunityStatus::Matricula->value,
    ]);

    $task = Task::factory()->create([
        'opportunity_id' => $opportunity->id,
        'school_id' => $opportunity->school_id,
        'type' => TaskType::Agendamento->value,
    ]);

    $outcome = Outcome::create([
        'name' => 'Nova Tentativa',
        'slug' => 'nova_tentativa',
        'task_type' => TaskType::Agendamento->value,
    ]);

    $outcome->actions()->create([
        'action_type' => OutcomeActionType::MoveStatus->value,
        'payload' => ['status' => OpportunityStatus::Visita->value],
        'order' => 1,
    ]);

    $service = app(OutcomeProcessorService::class);

    expect(fn() => $service->process($task, $outcome))
        ->toThrow(\DomainException::class, 'opportunity_status_terminal');
});
