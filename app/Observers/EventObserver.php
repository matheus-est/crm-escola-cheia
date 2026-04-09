<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Event;
use Illuminate\Support\Str;

class EventObserver
{
    public function creating(Event $event): void
    {
        if ($event->uuid === null) {
            $event->uuid = Str::uuid()->toString();
        }
    }
}
