<?php

declare(strict_types=1);

namespace App\Services\Task;

use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Opportunity;
use App\Models\Outcome;
use App\Models\Task;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TaskService
{
    public function __construct(
        protected readonly OutcomeProcessorService $outcomeProcessorService,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Task::query()
            ->with(['opportunity.student', 'assignedUser', 'outcome']);

        if (array_key_exists('opportunity_uuid', $filters) && $filters['opportunity_uuid'] !== null) {
            $query->whereHas('opportunity', function ($q) use ($filters): void {
                $q->where('uuid', $filters['opportunity_uuid']);
            });
        }

        if (array_key_exists('status', $filters) && $filters['status'] !== null) {
            $status = TaskStatus::tryFrom((string) $filters['status']);
            if ($status !== null) {
                $query->where('status', $status->value);
            }
        }

        if (array_key_exists('type', $filters) && $filters['type'] !== null) {
            $type = TaskType::tryFrom((string) $filters['type']);
            if ($type !== null) {
                $query->where('type', $type->value);
            }
        }

        if (array_key_exists('assigned_user_id', $filters) && $filters['assigned_user_id'] !== null) {
            $query->where('assigned_user_id', (int) $filters['assigned_user_id']);
        }

        if (array_key_exists('date_from', $filters) && $filters['date_from'] !== null) {
            $query->where('due_at', '>=', $filters['date_from']);
        }

        if (array_key_exists('date_to', $filters) && $filters['date_to'] !== null) {
            $query->where('due_at', '<=', $filters['date_to']);
        }

        if (array_key_exists('is_schedule', $filters) && $filters['is_schedule'] !== null) {
            $scheduleTypes = collect(TaskType::cases())
                ->filter(fn (TaskType $t) => $t->isSchedule())
                ->map(fn (TaskType $t) => $t->value)
                ->all();
            $query->whereIn('type', $scheduleTypes);
        }

        return $query->orderByDesc('created_at')->paginate(20);
    }

    public function create(Opportunity $opportunity, array $data): Task
    {
        $hasOpenTask = Task::query()
            ->where('opportunity_id', $opportunity->id)
            ->where('status', TaskStatus::Open->value)
            ->exists();

        if ($hasOpenTask) {
            throw new \DomainException('opportunity_has_open_task');
        }

        $task = Task::create([...$data, 'opportunity_id' => $opportunity->id]);

        if ($task->assigned_user_id !== null) {
            $task->load(['opportunity.student', 'assignedUser']);
            $task->assignedUser->notify(
                new TaskAssignedNotification($task)
            );
        }

        return $task;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function complete(Task $task, Outcome $outcome, array $payload = []): array
    {
        if ($task->status !== TaskStatus::Open) {
            throw new \DomainException('task_not_open');
        }

        return DB::transaction(function () use ($task, $outcome, $payload): array {
            $task->update([
                'status' => TaskStatus::Completed,
                'outcome_id' => $outcome->id,
                'completed_at' => now(),
                'notes' => $payload['notes'] ?? null,
                'refusal_category' => $payload['refusal_category'] ?? null,
                'refusal_detail' => $payload['refusal_detail'] ?? null,
            ]);

            return $this->outcomeProcessorService->process($task, $outcome, $payload);
        });
    }

    public function cancel(Task $task): void
    {
        $task->update([
            'status' => TaskStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
