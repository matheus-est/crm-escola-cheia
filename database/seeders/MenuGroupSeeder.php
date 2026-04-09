<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MenuGroup;
use Illuminate\Database\Seeder;

class MenuGroupSeeder extends Seeder
{
    protected array $groups = [
        [
            'name' => 'Configurações',
            'slug' => 'configuration',
            'icon' => 'Settings',
            'order' => 1,
            'is_active' => true,
        ],
        [
            'name' => 'Cadastro',
            'slug' => 'registration',
            'icon' => 'FolderInput',
            'order' => 2,
            'is_active' => true,
        ],
    ];

    public function run(): void
    {
        foreach ($this->groups as $group) {
            MenuGroup::updateOrCreate(
                ['slug' => $group['slug']],
                $group
            );
        }
    }
}
