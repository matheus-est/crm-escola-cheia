<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ModuleAction;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CrmPermissionSeeder extends Seeder
{
    private array $modules = [
        [
            'slug' => 'schools',
            'name' => 'Escolas',
            'icon' => 'Building2',
            'url' => '/admin/schools',
            'description' => 'Gestão de escolas do CRM',
            'order' => 10,
            'show_in_menu' => true,
            'is_active' => true,
        ],
        [
            'slug' => 'leads',
            'name' => 'Leads',
            'icon' => 'Users',
            'url' => '/leads',
            'description' => 'Gestão de captação',
            'order' => 11,
            'show_in_menu' => true,
            'is_active' => true,
        ],
        [
            'slug' => 'oportunidades',
            'name' => 'Oportunidades',
            'icon' => 'Target',
            'url' => '/opportunities',
            'description' => 'Gestão de matrículas e funil',
            'order' => 12,
            'show_in_menu' => true,
            'is_active' => true,
        ],
        [
            'slug' => 'tarefas',
            'name' => 'Tarefas',
            'icon' => 'CheckSquare',
            'url' => '/tasks',
            'description' => 'Gestão de tarefas e retornos',
            'order' => 13,
            'show_in_menu' => true,
            'is_active' => true,
        ],
    ];

    /**
     * Actions per module.
     *
     * @var array<string, list<string>>
     */
    private array $actions = [
        'schools' => ['view', 'list', 'add', 'edit', 'delete'],
        'leads' => ['view', 'list', 'add', 'edit', 'delete'],
        'oportunidades' => ['view', 'list', 'add', 'edit', 'delete'],
        'tarefas' => ['view', 'list', 'add', 'edit', 'complete'],
    ];

    public function run(): void
    {
        $this->createModulesAndPermissions();
        $this->assignPermissionsToRoles();
    }

    /**
     * Create (or update) CRM modules, module actions and permissions.
     */
    private function createModulesAndPermissions(): void
    {
        foreach ($this->modules as $mod) {
            $slug = $mod['slug'];
            $name = $mod['name'];

            $module = Module::updateOrCreate(
                ['slug' => $slug],
                $mod
            );

            foreach ($this->actions[$slug] as $order => $action) {
                ModuleAction::updateOrCreate(
                    [
                        'module_id' => $module->id,
                        'name' => $action,
                    ],
                    [
                        'label' => ucfirst($action),
                        'order' => $order + 1,
                    ]
                );

                Permission::updateOrCreate(
                    ['name' => "{$slug}_{$action}"],
                    [
                        'module_id' => $module->id,
                        'description' => ucfirst($action).' '.strtolower($name),
                    ]
                );
            }

            $this->command->info("CRM module [{$slug}]: permissions synced.");
        }
    }

    /**
     * Assign CRM permissions to each role using detach + attach (never sync).
     */
    private function assignPermissionsToRoles(): void
    {
        $allCrmPermissions = Permission::query()
            ->where(function ($query): void {
                foreach ($this->modules as $mod) {
                    $slug = $mod['slug'];
                    $query->orWhere('name', 'like', "{$slug}_%");
                }
            })
            ->get();

        $allCrmIds = $allCrmPermissions->pluck('id');

        /** @var array<string, list<string>> $rolePermissionMap */
        $rolePermissionMap = [
            'Master' => $allCrmIds->toArray(),

            'Admin' => $allCrmIds->toArray(),

            'Operacao' => $allCrmPermissions
                ->reject(fn (Permission $p): bool => $p->name === 'schools_delete')
                ->pluck('id')
                ->toArray(),

            'Gestor' => $allCrmPermissions
                ->filter(fn (Permission $p): bool => str_starts_with($p->name, 'leads_')
                    || str_starts_with($p->name, 'oportunidades_')
                    || str_starts_with($p->name, 'tarefas_'))
                ->pluck('id')
                ->toArray(),

            'Comercial' => $allCrmPermissions
                ->filter(fn (Permission $p): bool => in_array($p->name, [
                    'oportunidades_view',
                    'oportunidades_list',
                    'oportunidades_add',
                    'oportunidades_edit',
                    'tarefas_view',
                    'tarefas_list',
                    'tarefas_add',
                    'tarefas_complete',
                    'leads_view',
                    'leads_list',
                    'leads_add',
                    'leads_edit',
                ], strict: true))
                ->pluck('id')
                ->toArray(),
        ];

        foreach ($rolePermissionMap as $roleName => $permissionIds) {
            $role = Role::query()->where('name', $roleName)->first();

            if ($role === null) {
                $this->command->warn("Role [{$roleName}] not found. Skipping.");

                continue;
            }

            DB::transaction(function () use ($role, $allCrmIds, $permissionIds): void {
                $role->permissions()->detach($allCrmIds->toArray());
                $role->permissions()->attach($permissionIds);
            });

            $this->command->info("Role [{$roleName}]: ".count($permissionIds).' CRM permissions assigned.');
        }
    }
}
