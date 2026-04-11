<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskOverdueNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NotifyOverdueTasksCommand extends Command
{
    protected $signature = 'notifications:notify-overdue-tasks';

    protected $description = 'Envia notificações para tarefas abertas que passaram do prazo';

    public function handle(): int
    {
        $rows = DB::table('tasks')
            ->where('status', 'open')
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereNull('notified_overdue_at')
            ->get();

        $notified = 0;

        foreach ($rows as $row) {
            $task = Task::withoutGlobalScopes()
                ->with(['opportunity.student', 'opportunity.responsibleUser'])
                ->find($row->id);

            if ($task === null) {
                continue;
            }

            $user = $task->opportunity->responsibleUser ?? null;

            if ($user === null) {
                continue;
            }

            $user->notify(new TaskOverdueNotification($task));

            DB::table('tasks')
                ->where('id', $row->id)
                ->update(['notified_overdue_at' => now()]);

            $notified++;
        }

        $this->info("Notificadas {$notified} tarefas vencidas.");

        return self::SUCCESS;
    }
}
