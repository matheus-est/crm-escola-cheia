<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Str;

class UserObserver
{
    /**
     * Handle the User "creating" event.
     */
    public function creating(User $model): void
    {
        if (empty($model->uuid)) {
            $model->uuid = (string) Str::uuid();
        }
    }
}
