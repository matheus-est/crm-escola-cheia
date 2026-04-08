<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SchoolStatus;
use App\Models\School;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<School>
 */
class SchoolFactory extends Factory
{
    protected $model = School::class;

    public function definition(): array
    {
        return [
            'cnpj' => fake()->numerify('##############'),
            'razao_social' => fake()->company(),
            'status' => SchoolStatus::Active->value,
        ];
    }
}
