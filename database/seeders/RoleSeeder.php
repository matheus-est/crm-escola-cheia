<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = Permission::all();

        $master = Role::updateOrCreate([
            'name' => 'Master',
        ],
            [
                'description' => 'Perfil master com acesso total ao sistema',
                'is_default' => false,
            ]);
        $master->permissions()->sync($permissions->pluck('id'));

        $admin = Role::updateOrCreate([
            'name' => 'Admin',
        ],
            [
                'description' => 'Perfil de administrador com permissões completas',
                'is_default' => false,
            ]);
        $admin->permissions()->sync(
            Permission::whereIn('name', ['dashboard_view', 'users_list'])->pluck('id')
        );

        $user = Role::updateOrCreate([
            'name' => 'User',
        ],
            [
                'description' => 'Perfil padrão para usuários',
                'is_default' => true,
            ]);
        $user->permissions()->sync(
            Permission::whereIn('name', ['dashboard_view', 'users_list'])->pluck('id')
        );

        // CRM roles — cross-tenant and tenant-scoped profiles
        Role::updateOrCreate(
            ['name' => 'Operacao'],
            [
                'description' => 'Perfil de operação com acesso cross-tenant',
                'is_default' => false,
            ]
        );

        Role::updateOrCreate(
            ['name' => 'Gestor'],
            [
                'description' => 'Perfil de gestor com acesso ao próprio tenant',
                'is_default' => false,
            ]
        );

        Role::updateOrCreate(
            ['name' => 'Comercial'],
            [
                'description' => 'Perfil comercial com acesso operacional ao tenant',
                'is_default' => false,
            ]
        );
    }
}
