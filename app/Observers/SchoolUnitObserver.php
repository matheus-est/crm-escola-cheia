<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\SchoolUnit;
use Illuminate\Support\Str;

class SchoolUnitObserver
{
    public function creating(SchoolUnit $unit): void
    {
        if ($unit->uuid === null) {
            $unit->uuid = (string) Str::uuid();
        }
    }
}
