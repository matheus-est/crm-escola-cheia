<?php

declare(strict_types=1);

namespace App\Services\Task;

use App\Enums\OpportunityStatus;
use App\Enums\OutcomeActionType;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Exceptions\RenitenteLimitReachedException;
use App\Models\Opportunity;
use App\Models\Outcome;
use App\Models\OutcomeAction;
use App\Models\Task;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OutcomeProcessorService
{
    public function __construct(
        protected readonly RenitenteCycleService $renitenteCycleService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function process(Task $task, Outcome $outcome, array $payload = []): array
    {
        $result = ['open_window' => null];

        $actions = $outcome->actions()->orderBy('order')->get();

        foreach ($actions as $action) {
            $this->executeAction($task, $action, $payload, $result);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $result
     */
    private function executeAction(Task $task, OutcomeAction $action, array $payload, array &$result): void
    {
        $type = OutcomeActionType::tryFrom($action->action_type);
        $actionPayload = $action->payload ?? [];
        $opportunity = $task->opportunity;

        match ($type) {
            OutcomeActionType::MoveStatus => $this->moveStatus($opportunity, $actionPayload, $payload),
            OutcomeActionType::CreateTask => $this->createTask($task, $actionPayload),
            OutcomeActionType::CancelTasks => $this->cancelTasks($task, $actionPayload),
            OutcomeActionType::OpenWindow => $this->openWindow($actionPayload, $result),
            null => null,
        };
    }

    /** @param array<string, mixed> $actionPayload */
    private function moveStatus(?Opportunity $opportunity, array $actionPayload, array $payload): void
    {
        if ($opportunity === null) {
            return;
        }

        if (is_string($opportunity->status)) {
            $currentStatus = OpportunityStatus::tryFrom($opportunity->status);
        } else {
            $currentStatus = $opportunity->status;
        }

        if ($currentStatus !== null && $currentStatus->isTerminal()) {
            throw new \DomainException('opportunity_status_terminal');
        }

        $status = OpportunityStatus::tryFrom((string) ($actionPayload['status'] ?? ''));

        if ($status === null) {
            return;
        }

        if ($status === OpportunityStatus::Recusado && empty($payload['refusal_category'])) {
            throw ValidationException::withMessages([
                'refusal_category' => 'A categoria de recusa é obrigatória.',
            ]);
        }

        $opportunity->updateQuietly(['status' => $status->value, 'status_changed_at' => now()]);
    }

    /** @param array<string, mixed> $actionPayload */
    private function createTask(Task $task, array $actionPayload): void
    {
        $opportunity = $task->opportunity;

        if ($opportunity === null) {
            return;
        }

        $taskType = TaskType::tryFrom((string) ($actionPayload['task_type'] ?? ''));

        if ($taskType === null) {
            return;
        }

        $isRenitente = (bool) ($actionPayload['renitente'] ?? false);

        $dueAt = null;

        if ($isRenitente) {
            try {
                $dueAt = $this->renitenteCycleService->nextDueAt($opportunity);
            } catch (RenitenteLimitReachedException) {
                // Limit reached — renitente_count reset; do not create new task
                return;
            }
        } elseif (array_key_exists('delay_days', $actionPayload) && $actionPayload['delay_days'] !== null) {
            $dueAt = now()->addDays((int) $actionPayload['delay_days']);
        }

        DB::table('tasks')->insert([
            'uuid' => (string) Str::uuid(),
            'school_id' => $opportunity->school_id,
            'opportunity_id' => $opportunity->id,
            'type' => $taskType->value,
            'status' => TaskStatus::Open->value,
            'assigned_user_id' => $task->assigned_user_id,
            'due_at' => $dueAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @param array<string, mixed> $actionPayload */
    private function cancelTasks(Task $task, array $actionPayload): void
    {
        $opportunity = $task->opportunity;

        if ($opportunity === null) {
            return;
        }

        $taskType = TaskType::tryFrom((string) ($actionPayload['task_type'] ?? ''));

        $query = Task::query()
            ->where('opportunity_id', $opportunity->id)
            ->where('status', TaskStatus::Open->value)
            ->where('id', '!=', $task->id);

        if ($taskType !== null) {
            $query->where('type', $taskType->value);
        }

        $query->update([
            'status' => TaskStatus::Cancelled->value,
            'cancelled_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $actionPayload
     * @param  array<string, mixed>  $result
     */
    private function openWindow(array $actionPayload, array &$result): void
    {
        $window = $actionPayload['window'] ?? null;

        if ($window !== null) {
            $result['open_window'] = $window;
        }
    }
}
