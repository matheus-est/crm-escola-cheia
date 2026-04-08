<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Segment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Segment> */
class SegmentFactory extends Factory
{
    protected $model = Segment::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'name' => fake()->randomElement([
                'Educação Infantil',
                'Ensino Fundamental I',
                'Ensino Fundamental II',
                'Ensino Médio',
            ]).' '.fake()->randomDigit(),
        ];
    }
}
