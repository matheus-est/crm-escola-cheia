<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Module;
use Illuminate\Support\Str;

class ModuleObserver
{
    /**
     * Handle the Module "creating" event.
     */
    public function creating(Module $model): void
    {
        if (empty($model->uuid)) {
            $model->uuid = (string) Str::uuid();
        }
    }
}
