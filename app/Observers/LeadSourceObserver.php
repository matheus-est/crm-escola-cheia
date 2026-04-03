<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\LeadSource;
use Illuminate\Support\Str;

class LeadSourceObserver
{
    public function creating(LeadSource $leadSource): void
    {
        if ($leadSource->uuid === null) {
            $leadSource->uuid = (string) Str::uuid();
        }
    }
}
