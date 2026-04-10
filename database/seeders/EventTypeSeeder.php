<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EventType;
use Illuminate\Database\Seeder;

class EventTypeSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Palestra', 'Workshop', 'Visita'] as $name) {
            EventType::withoutEventTypeScope()->firstOrCreate(
                ['name' => $name, 'school_id' => null, 'is_system' => true],
                ['is_active' => true],
            );
        }
    }
}
