<?php

declare(strict_types=1);

namespace App\Services\Task;

use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Opportunity;
use App\Models\Outcome;
use App\Models\Task;
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

        return Task::create([...$data, 'opportunity_id' => $opportunity->id]);
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
