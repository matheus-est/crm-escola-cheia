<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\School;
use Illuminate\Support\Str;

class SchoolObserver
{
    public function creating(School $school): void
    {
        if ($school->uuid === null) {
            $school->uuid = (string) Str::uuid();
        }
    }
}
