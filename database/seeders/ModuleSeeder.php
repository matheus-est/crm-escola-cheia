<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MenuGroup;
use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $configGroup = MenuGroup::where('slug', 'configuration')->first();
        $registrationGroup = MenuGroup::where('slug', 'registration')->first();
        $tenantSettingsGroup = MenuGroup::where('slug', 'tenant_settings')->first();

        $modules = [
            [
                'name' => 'Dashboard',
                'slug' => 'dashboard',
                'icon' => 'LayoutGrid',
                'url' => '/dashboard',
                'description' => 'Painel administrativo com métricas e gráficos',
                'order' => 1,
                'show_in_menu' => true,
                'is_active' => true,
                'menu_group_id' => null,
            ],
            [
                'name' => 'Usuários',
                'slug' => 'users',
                'icon' => 'Users',
                'url' => '/acl/users',
                'description' => 'Gerenciamento de usuários',
                'order' => 1,
                'show_in_menu' => true,
                'is_active' => true,
                'menu_group_id' => $registrationGroup?->id,
            ],
            [
                'name' => 'Perfis',
                'slug' => 'roles',
                'icon' => 'UserCog',
                'url' => '/acl/roles',
                'description' => 'Gerenciamento de perfis',
                'order' => 1,
                'show_in_menu' => true,
                'is_active' => true,
                'menu_group_id' => $configGroup?->id,
            ],
            [
                'name' => 'Permissões',
                'slug' => 'permissions',
                'icon' => 'Shield',
                'url' => '',
                'description' => 'Gerenciamento de permissões',
                'order' => 4,
                'show_in_menu' => false,
                'is_active' => false,
                'menu_group_id' => $configGroup?->id,
            ],
            [
                'name' => 'Módulos',
                'slug' => 'modules',
                'icon' => 'Cog',
                'url' => '/acl/modules',
                'description' => 'Gerenciamento de módulos',
                'order' => 2,
                'show_in_menu' => true,
                'is_active' => true,
                'menu_group_id' => $configGroup?->id,
            ],
            [
                'name' => 'Grupos de Menu',
                'slug' => 'menu_groups',
                'icon' => 'FolderInput',
                'url' => '/acl/menu-groups',
                'description' => 'Gerenciamento de grupos de menu',
                'order' => 3,
                'show_in_menu' => true,
                'is_active' => true,
                'menu_group_id' => $configGroup?->id,
            ],
            [
                'name' => 'Termos de Uso',
                'slug' => 'terms',
                'icon' => 'FileText',
                'url' => '/acl/terms',
                'description' => 'Gerenciamento de Termos de Uso',
                'order' => 5,
                'show_in_menu' => true,
                'is_active' => true,
                'menu_group_id' => $configGroup?->id,
            ],
            [
                'name' => 'Configurações',
                'slug' => 'settings',
                'icon' => 'Settings',
                'url' => '/settings/system',
                'description' => 'Gerenciamento de configurações do sistema',
                'order' => 6,
                'show_in_menu' => true,
                'is_active' => true,
                'menu_group_id' => $configGroup?->id,
            ],
            [
                'name' => 'Anos Letivos',
                'slug' => 'school_years',
                'icon' => 'CalendarDays',
                'url' => '/tenant-settings/school-years',
                'description' => 'Gestão de anos letivos do tenant',
                'order' => 1,
                'show_in_menu' => true,
                'is_active' => true,
                'menu_group_id' => $tenantSettingsGroup?->id,
            ],
            [
                'name' => 'Origens de Lead',
                'slug' => 'lead_sources',
                'icon' => 'Tags',
                'url' => '/tenant-settings/lead-sources',
                'description' => 'Gestão de origens de lead do tenant',
                'order' => 2,
                'show_in_menu' => false,
                'is_active' => true,
                'menu_group_id' => $tenantSettingsGroup?->id,
            ],
            [
                'name' => 'Turmas/Séries',
                'slug' => 'grades',
                'icon' => 'GraduationCap',
                'url' => '/tenant-settings/grades',
                'description' => 'Gestão de turmas e séries do tenant',
                'order' => 3,
                'show_in_menu' => true,
                'is_active' => true,
                'menu_group_id' => $tenantSettingsGroup?->id,
            ],
            [
                'name' => 'Salas',
                'slug' => 'salas',
                'icon' => 'DoorOpen',
                'url' => '/tenant/tenant-settings/rooms',
                'description' => 'Gestão de salas do tenant',
                'order' => 4,
                'show_in_menu' => true,
                'is_active' => true,
                'menu_group_id' => $tenantSettingsGroup?->id,
            ],
        ];

        foreach ($modules as $module) {
            Module::updateOrCreate(
                ['slug' => $module['slug']],
                $module
            );
        }
    }
}
