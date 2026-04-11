<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateLembreteAgendaCommand extends Command
{
    protected $signature = 'tasks:create-lembrete-agenda';

    protected $description = 'Cria tarefas lembrete_agenda para agendamentos que vencem nas próximas 24h';

    public function handle(): int
    {
        $agendamentos = Task::withoutGlobalScopes()
            ->where('type', TaskType::Agendamento->value)
            ->where('status', TaskStatus::Open->value)
            ->where('due_at', '<=', now()->addHours(24))
            ->where('due_at', '>=', now())
            ->get();

        $created = 0;

        foreach ($agendamentos as $task) {
            $hasOpenTask = Task::withoutGlobalScopes()
                ->where('opportunity_id', $task->opportunity_id)
                ->where('status', TaskStatus::Open->value)
                ->where('id', '!=', $task->id)
                ->exists();

            if ($hasOpenTask) {
                continue;
            }

            DB::table('tasks')->insert([
                'uuid' => (string) Str::uuid(),
                'school_id' => $task->school_id,
                'opportunity_id' => $task->opportunity_id,
                'assigned_user_id' => $task->assigned_user_id,
                'type' => TaskType::LembreteAgenda->value,
                'status' => TaskStatus::Open->value,
                'due_at' => $task->due_at,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $created++;
        }

        $this->info("Criados {$created} lembretes de agenda.");

        return self::SUCCESS;
    }
}
