<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MenuGroup;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        [
            'all' => $allPermissions,
            'nonConfig' => $nonConfigPermissions,
            'withoutDelete' => $permissionsWithoutDelete,
            'manager' => $managerPermissions,
        ] = $this->resolvePermissionSets();

        $this->seedMaster($allPermissions);
        $this->seedAdmin($nonConfigPermissions);
        $this->seedOperation($permissionsWithoutDelete);
        $this->seedManager($managerPermissions);
        $this->seedCommercial();
        $this->seedDefaultUser();
    }

    // -------------------------------------------------------------------------
    // Permission Resolution
    // -------------------------------------------------------------------------

    private function resolvePermissionSets(): array
    {
        $configGroup = MenuGroup::where('slug', 'configuration')->first();

        $nonConfigModuleIds = Module::query()
            ->when($configGroup, fn ($q) => $q->where(function ($sub) use ($configGroup) {
                $sub->whereNull('menu_group_id')
                    ->orWhere('menu_group_id', '!=', $configGroup->id);
            }))
            ->pluck('id');

        $managerModuleIds = Module::whereIn('slug', [
            'tasks',
            'opportunities',
            'events',
            'school_years',
            'lead_sources',
            'grades',
            'rooms',
            'event_types',
        ])->pluck('id');

        return [
            'all' => Permission::pluck('id'),
            'nonConfig' => Permission::whereIn('module_id', $nonConfigModuleIds)->pluck('id'),
            'withoutDelete' => Permission::where('name', 'NOT LIKE', '%_delete')->pluck('id'),
            'manager' => Permission::whereIn('module_id', $managerModuleIds)->pluck('id'),
        ];
    }

    private function permissionIds(array $names): Collection
    {
        return Permission::whereIn('name', $names)->pluck('id');
    }

    // -------------------------------------------------------------------------
    // Role Seeders
    // -------------------------------------------------------------------------

    private function seedMaster(Collection $permissions): void
    {
        $this->upsertRole(
            name: 'Master',
            description: 'Perfil master com acesso total ao sistema',
            permissions: $permissions,
        );
    }

    private function seedAdmin(Collection $permissions): void
    {
        $this->upsertRole(
            name: 'Admin',
            description: 'Administrador com permissões completas, exceto configurações',
            permissions: $permissions,
        );
    }

    private function seedOperation(Collection $permissions): void
    {
        $this->upsertRole(
            name: 'Operação',
            description: 'Acesso amplo ao sistema sem permissões de exclusão',
            permissions: $permissions,
        );
    }

    private function seedManager(Collection $permissions): void
    {
        $this->upsertRole(
            name: 'Gestor',
            description: 'Acesso limitado aos módulos operacionais do tenant',
            permissions: $permissions,
        );
    }

    private function seedCommercial(): void
    {
        $this->upsertRole(
            name: 'Comercial',
            description: 'Acesso operacional focado em oportunidades e tarefas',
            permissions: $this->permissionIds([
                'dashboard_view',
                'opportunities_view',
                'opportunities_list',
                'opportunities_add',
                'opportunities_edit',
                'tasks_view',
                'tasks_list',
                'tasks_add',
                'tasks_complete',
                'school_years_view',
                'school_years_list',
                'lead_sources_view',
                'lead_sources_list',
                'grades_view',
                'grades_list',
                'events_view',
                'events_list',
                'rooms_view',
                'rooms_list',
            ]),
        );
    }

    private function seedDefaultUser(): void
    {
        $this->upsertRole(
            name: 'User',
            description: 'Perfil padrão com acesso mínimo ao sistema',
            permissions: $this->permissionIds(['dashboard_view']),
            isDefault: true,
        );
    }

    private function upsertRole(
        string $name,
        string $description,
        Collection $permissions,
        bool $isDefault = false,
    ): Role {
        $role = Role::updateOrCreate(
            ['name' => $name],
            ['description' => $description, 'is_default' => $isDefault],
        );

        $role->permissions()->sync($permissions);

        return $role;
    }
}
