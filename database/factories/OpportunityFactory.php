<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OpportunityStatus;
use App\Models\Opportunity;
use App\Models\School;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Opportunity>
 */
class OpportunityFactory extends Factory
{
    protected $model = Opportunity::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'school_id' => School::factory(),
            'student_id' => Student::factory(),
            'status' => OpportunityStatus::CadastroInicial->value,
            'renitente_count' => 0,
        ];
    }
}
