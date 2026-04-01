<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\MenuGroup;
use Illuminate\Support\Str;

class MenuGroupObserver
{
    /**
     * Handle the MenuGroup "creating" event.
     */
    public function creating(MenuGroup $model): void
    {
        if (empty($model->uuid)) {
            $model->uuid = (string) Str::uuid();
        }
    }
}
