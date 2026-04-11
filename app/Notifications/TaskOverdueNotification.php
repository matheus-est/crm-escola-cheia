<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TaskOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Task $task)
    {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'task_uuid' => $this->task->uuid,
            'task_type_label' => $this->task->type?->label() ?? '',
            'opportunity_uuid' => $this->task->opportunity->uuid,
            'opportunity_student_name' => $this->task->opportunity->student->name ?? '',
            'due_at' => $this->task->due_at?->toIso8601String(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
