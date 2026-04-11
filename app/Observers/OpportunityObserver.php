<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\TaskStatus;
use App\Models\Opportunity;
use App\Models\Task;
use Illuminate\Support\Str;

class OpportunityObserver
{
    public function creating(Opportunity $opportunity): void
    {
        if ($opportunity->uuid === null) {
            $opportunity->uuid = (string) Str::uuid();
        }
    }

    public function updated(Opportunity $opportunity): void
    {
        if (! $opportunity->isDirty('status')) {
            return;
        }

        $opportunity->updateQuietly(['status_changed_at' => now()]);

        if ($opportunity->status->isTerminal()) {
            Task::query()
                ->where('opportunity_id', $opportunity->id)
                ->where('status', TaskStatus::Open->value)
                ->get()
                ->each(function (Task $task): void {
                    $task->update([
                        'status' => TaskStatus::Cancelled,
                        'cancelled_at' => now(),
                    ]);
                });
        }
    }
}
