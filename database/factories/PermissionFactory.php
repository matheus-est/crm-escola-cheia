<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'name' => fake()->unique()->word().'_'.fake()->randomElement(['list', 'add', 'edit', 'view', 'delete']),
            'module_id' => Module::factory(),
            'description' => fake()->sentence(),
        ];
    }
}
