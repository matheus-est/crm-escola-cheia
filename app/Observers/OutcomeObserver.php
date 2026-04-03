<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Outcome;
use Illuminate\Support\Str;

class OutcomeObserver
{
    /**
     * Handle the Outcome "creating" event.
     */
    public function creating(Outcome $outcome): void
    {
        if (empty($outcome->uuid)) {
            $outcome->uuid = (string) Str::uuid();
        }
    }

    /**
     * Handle the Outcome "updated" event.
     */
    public function updated(Outcome $outcome): void
    {
        //
    }

    /**
     * Handle the Outcome "deleted" event.
     */
    public function deleted(Outcome $outcome): void
    {
        //
    }

    /**
     * Handle the Outcome "restored" event.
     */
    public function restored(Outcome $outcome): void
    {
        //
    }

    /**
     * Handle the Outcome "force deleted" event.
     */
    public function forceDeleted(Outcome $outcome): void
    {
        //
    }
}
