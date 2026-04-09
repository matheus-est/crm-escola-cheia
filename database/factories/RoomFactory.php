<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Room;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Room> */
class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'school_id' => School::factory(),
            'name' => 'Sala '.fake()->numberBetween(1, 20),
            'capacity' => fake()->optional()->numberBetween(10, 50),
            'is_external' => false,
        ];
    }
}
