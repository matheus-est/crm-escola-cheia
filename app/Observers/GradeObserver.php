<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Grade;
use Illuminate\Support\Str;

class GradeObserver
{
    public function creating(Grade $grade): void
    {
        if ($grade->uuid === null) {
            $grade->uuid = (string) Str::uuid();
        }
    }
}
