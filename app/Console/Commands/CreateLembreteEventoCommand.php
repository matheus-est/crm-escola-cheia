<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Event;
use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateLembreteEventoCommand extends Command
{
    protected $signature = 'tasks:create-lembrete-evento';

    protected $description = 'Cria tarefas lembrete_evento para eventos que ocorrem nas próximas 24h';

    public function handle(): int
    {
        $events = Event::withoutGlobalScopes()
            ->where('event_date', '<=', now()->addHours(24))
            ->where('event_date', '>=', now())
            ->with('opportunities')
            ->get();

        $created = 0;

        foreach ($events as $event) {
            foreach ($event->opportunities as $opportunity) {
                $hasOpenTask = Task::withoutGlobalScopes()
                    ->where('opportunity_id', $opportunity->id)
                    ->where('status', TaskStatus::Open->value)
                    ->exists();

                if ($hasOpenTask) {
                    continue;
                }

                DB::table('tasks')->insert([
                    'uuid' => (string) Str::uuid(),
                    'school_id' => $opportunity->school_id,
                    'opportunity_id' => $opportunity->id,
                    'type' => TaskType::LembreteEvento->value,
                    'status' => TaskStatus::Open->value,
                    'due_at' => $event->event_date,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $created++;
            }
        }

        $this->info("Criados {$created} lembretes de evento.");

        return self::SUCCESS;
    }
}
