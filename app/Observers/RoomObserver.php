<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Room;
use Illuminate\Support\Str;

class RoomObserver
{
    public function creating(Room $room): void
    {
        if ($room->uuid === null) {
            $room->uuid = (string) Str::uuid();
        }
    }
}
