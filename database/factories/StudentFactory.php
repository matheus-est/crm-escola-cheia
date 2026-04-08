<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\School;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    protected $model = Student::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'school_id' => School::factory(),
            'nome' => fake()->name(),
            'cpf' => null,
            'data_nascimento' => null,
        ];
    }
}
