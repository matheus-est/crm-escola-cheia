<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\SchoolYear;
use Illuminate\Support\Str;

class SchoolYearObserver
{
    public function creating(SchoolYear $schoolYear): void
    {
        if ($schoolYear->uuid === null) {
            $schoolYear->uuid = (string) Str::uuid();
        }
    }
}
