<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Grade;
use App\Models\School;
use App\Models\Segment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Grade> */
class GradeFactory extends Factory
{
    protected $model = Grade::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'school_id' => School::factory(),
            'segment_id' => Segment::factory(),
            'name' => fake()->randomElement(['1º', '2º', '3º', '4º', '5º']).' Ano '.fake()->randomLetter(),
            'order' => fake()->numberBetween(1, 20),
        ];
    }
}
