<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    protected $model = Module::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'uuid' => (string) Str::uuid(),
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'icon' => 'Circle',
            'url' => '/'.Str::slug($name),
            'description' => fake()->sentence(),
            'order' => fake()->numberBetween(1, 100),
            'is_active' => true,
            'show_in_menu' => false,
            'menu_group_id' => null,
        ];
    }
}
