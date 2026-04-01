<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Permission;
use Illuminate\Support\Str;

class PermissionObserver
{
    /**
     * Handle the Permission "creating" event.
     */
    public function creating(Permission $model): void
    {
        if (empty($model->uuid)) {
            $model->uuid = (string) Str::uuid();
        }
    }
}
