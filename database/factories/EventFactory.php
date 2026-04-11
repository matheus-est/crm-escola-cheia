<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Event;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'school_id' => School::factory(),
            'title' => $this->faker->sentence(3),
            'event_type_id' => null,
            'has_no_date' => false,
            'grade_id' => null,
            'event_date' => now()->addDays(7),
            'location' => $this->faker->address(),
            'max_capacity' => null,
        ];
    }
}
