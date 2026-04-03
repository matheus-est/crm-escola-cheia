<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Segment;
use Illuminate\Support\Str;

class SegmentObserver
{
    public function creating(Segment $segment): void
    {
        if ($segment->uuid === null) {
            $segment->uuid = (string) Str::uuid();
        }
    }
}
