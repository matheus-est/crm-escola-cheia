<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Opportunity;
use Illuminate\Support\Str;

class OpportunityObserver
{
    public function creating(Opportunity $opportunity): void
    {
        if ($opportunity->uuid === null) {
            $opportunity->uuid = (string) Str::uuid();
        }
    }
}
