<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Student;
use Illuminate\Support\Str;

class StudentObserver
{
    public function creating(Student $student): void
    {
        if ($student->uuid === null) {
            $student->uuid = (string) Str::uuid();
        }
    }
}
