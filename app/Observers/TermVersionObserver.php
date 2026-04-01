<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\TermVersion;
use Illuminate\Support\Str;

class TermVersionObserver
{
    /**
     * Handle the TermVersion "creating" event.
     */
    public function creating(TermVersion $model): void
    {
        if (empty($model->uuid)) {
            $model->uuid = (string) Str::uuid();
        }
    }
}
