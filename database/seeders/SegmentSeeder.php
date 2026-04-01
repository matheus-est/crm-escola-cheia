<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Segment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SegmentSeeder extends Seeder
{
    public function run(): void
    {
        $segments = [
            'Berçário',
            'Ed. Infantil',
            'Fund. I',
            'Fund. II',
            'Ensino Médio',
            'Integral',
        ];

        foreach ($segments as $name) {
            Segment::firstOrCreate(
                ['name' => $name],
                ['uuid' => (string) Str::uuid()],
            );
        }
    }
}
