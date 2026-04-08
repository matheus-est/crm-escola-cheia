<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Opportunity;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'opportunity_id' => Opportunity::factory(),
            'type' => TaskType::RetornoLigacao->value,
            'status' => TaskStatus::Open->value,
            'due_at' => now()->addHour(),
        ];
    }
}
