<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\OutcomeAction;
use Illuminate\Support\Str;

class OutcomeActionObserver
{
    /**
     * Handle the OutcomeAction "creating" event.
     */
    public function creating(OutcomeAction $outcomeAction): void
    {
        if (empty($outcomeAction->uuid)) {
            $outcomeAction->uuid = (string) Str::uuid();
        }
    }

    /**
     * Handle the OutcomeAction "updated" event.
     */
    public function updated(OutcomeAction $outcomeAction): void
    {
        //
    }

    /**
     * Handle the OutcomeAction "deleted" event.
     */
    public function deleted(OutcomeAction $outcomeAction): void
    {
        //
    }

    /**
     * Handle the OutcomeAction "restored" event.
     */
    public function restored(OutcomeAction $outcomeAction): void
    {
        //
    }

    /**
     * Handle the OutcomeAction "force deleted" event.
     */
    public function forceDeleted(OutcomeAction $outcomeAction): void
    {
        //
    }
}
