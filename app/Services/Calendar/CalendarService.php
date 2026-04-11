<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use App\Enums\TaskType;
use App\Models\Event;
use App\Models\Task;

class CalendarService
{
    /** @return array<int, array<string, mixed>> */
    public function listEntries(string $dateFrom, string $dateTo, ?int $assignedUserId = null): array
    {
        $scheduleTypes = array_map(
            fn (TaskType $t): string => $t->value,
            array_filter(TaskType::cases(), fn (TaskType $t): bool => $t->isSchedule()),
        );

        $tasks = Task::query()
            ->with(['opportunity.student', 'assignedUser'])
            ->whereIn('type', $scheduleTypes)
            ->whereBetween('due_at', [$dateFrom, $dateTo])
            ->when($assignedUserId !== null, fn ($q) => $q->where('assigned_user_id', $assignedUserId))
            ->get();

        $events = Event::query()
            ->whereBetween('event_date', [$dateFrom, $dateTo])
            ->where('has_no_date', false)
            ->get();

        $taskEntries = $tasks->map(function (Task $task): array {
            $studentName = $task->opportunity?->student?->name ?? '';
            $typeLabel = $task->type->label();

            return [
                'uuid'          => $task->uuid,
                'title'         => trim("{$studentName} — {$typeLabel}"),
                'date'          => $task->due_at?->format('Y-m-d H:i:s') ?? '',
                'type'          => 'task',
                'sub_type'      => $task->type->value,
                'status'        => $task->status->value,
                'link_uuid'     => $task->opportunity?->uuid ?? '',
                'assigned_user' => $task->assignedUser?->name,
            ];
        });

        $eventEntries = $events->map(function (Event $event): array {
            return [
                'uuid'          => $event->uuid,
                'title'         => $event->title,
                'date'          => $event->event_date?->format('Y-m-d H:i:s') ?? '',
                'type'          => 'event',
                'sub_type'      => 'event',
                'status'        => null,
                'link_uuid'     => $event->uuid,
                'assigned_user' => null,
            ];
        });

        return collect($taskEntries->all())
            ->merge($eventEntries->all())
            ->sortBy('date')
            ->values()
            ->toArray();
    }
}
