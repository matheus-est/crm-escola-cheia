<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\ModuleAction;
use Illuminate\Support\Str;

class ModuleActionObserver
{
    /**
     * Handle the ModuleAction "creating" event.
     */
    public function creating(ModuleAction $model): void
    {
        if (empty($model->uuid)) {
            $model->uuid = (string) Str::uuid();
        }
    }
}
