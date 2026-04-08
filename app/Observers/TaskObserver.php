<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Task;
use Illuminate\Support\Str;

class TaskObserver
{
    public function creating(Task $task): void
    {
        if (empty($task->uuid)) {
            $task->uuid = (string) Str::uuid();
        }
    }
}
