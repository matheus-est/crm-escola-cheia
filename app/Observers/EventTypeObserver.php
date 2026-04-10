<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\EventType;
use Illuminate\Support\Str;

class EventTypeObserver
{
    public function creating(EventType $eventType): void
    {
        if ($eventType->uuid === null) {
            $eventType->uuid = Str::uuid()->toString();
        }
    }
}
