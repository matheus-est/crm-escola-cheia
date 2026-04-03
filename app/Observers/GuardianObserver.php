<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Guardian;
use Illuminate\Support\Str;

class GuardianObserver
{
    public function creating(Guardian $guardian): void
    {
        if ($guardian->uuid === null) {
            $guardian->uuid = (string) Str::uuid();
        }
    }
}
