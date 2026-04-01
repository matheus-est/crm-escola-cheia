<?php

declare(strict_types=1);

namespace App\Services\Acl;

use App\Models\MenuGroup;
use App\Models\Module;
use App\Models\ModuleAction;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\Menu\MenuService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ModuleService
{
    public function __construct(
        protected readonly MenuService $menuService,
        protected readonly RoleService $roleService,
        protected readonly UserService $userService,
    ) {}

    public function getAll(): Collection
    {
        return Module::with('actions')->orderBy('order')->get();
    }

    public function getAllForUser(User $user): array
    {
        return Module::with('actions')
            ->orderBy('order')
            ->get()
            ->map(fn ($module) => [
                'id' => $module->id,
                'uuid' => $module->uuid,
                'name' => $module->name,
                'slug' => $module->slug,
                'icon' => $module->icon,
                'url' => $module->url,
                'description' => $module->description,
                'is_active' => $module->is_active,
                'show_in_menu' => $module->show_in_menu,
                'order' => $module->order,
                'actions_count' => $module->actions->count(),
                'created_at' => $module->created_at,
                'can_view' => $this->userService->canAccess($user, $module->slug, 'view'),
                'can_edit' => $this->userService->canAccess($user, $module->slug, 'edit'),
                'can_delete' => $this->userService->canAccess($user, $module->slug, 'delete'),
            ])
            ->toArray();
    }

    public function filterForUser(User $user, array $filters): LengthAwarePaginator
    {
        $perPage = $filters['per_page'] ?? 10;
        $perPage = $perPage === 'all' || (int) $perPage === 0
            ? PHP_INT_MAX
            : (int) $perPage;

        return Module::with('actions')
            ->orderBy('order')
            ->paginate($perPage)
            ->through(fn ($module) => [
                'id' => $module->id,
                'uuid' => $module->uuid,
                'name' => $module->name,
                'slug' => $module->slug,
                'icon' => $module->icon,
                'url' => $module->url,
                'description' => $module->description,
                'is_active' => $module->is_active,
                'show_in_menu' => $module->show_in_menu,
                'order' => $module->order,
                'actions_count' => $module->actions->count(),
                'created_at' => $module->created_at,
                'can_view' => $this->userService->canAccess($user, $module->slug, 'view'),
                'can_edit' => $this->userService->canAccess($user, $module->slug, 'edit'),
                'can_delete' => $this->userService->canAccess($user, $module->slug, 'delete'),
            ]);
    }

    public function getById(int $id): Module
    {
        return Module::with('actions')->findOrFail($id);
    }

    public function getByUuid(string $uuid): Module
    {
        return Module::with('actions')->where('uuid', $uuid)->firstOrFail();
    }

    public function getMenuGroups(): array
    {
        return MenuGroup::orderByRaw("CASE WHEN slug = 'configuration' THEN 1 ELSE 0 END")
            ->orderBy('order')
            ->get()
            ->map(fn ($group) => [
                'id' => $group->id,
                'uuid' => $group->uuid,
                'name' => $group->name,
            ])
            ->toArray();
    }

    /**
     * Returns modules formatted with actions and permissions,
     * used by role management screens (create, edit, permissions).
     */
    public function getFormattedForPermissions(): array
    {
        return Module::with(['actions', 'permissions'])
            ->orderBy('order')
            ->get()
            ->map(fn ($module) => [
                'id' => $module->id,
                'uuid' => $module->uuid,
                'name' => $module->name,
                'slug' => $module->slug,
                'icon' => $module->icon,
                'actions' => $module->actions
                    ->map(fn ($action) => [
                        'id' => $action->id,
                        'uuid' => $action->uuid,
                        'name' => $action->name,
                        'label' => $action->label,
                    ])
                    ->toArray(),
                'permissions' => $module->permissions
                    ->map(fn ($p) => [
                        'id' => $p->id,
                        'uuid' => $p->uuid,
                        'name' => $p->name,
                        'action' => $module->actions
                            ->firstWhere('name', Str::after($p->name, $module->slug.'_'))
                            ?->name,
                    ])
                    ->toArray(),
            ])
            ->toArray();
    }

    public function update(Module $module, array $data): Module
    {
        $module->update([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'icon' => $data['icon'],
            'url' => $data['url'] ?? null,
            'description' => $data['description'] ?? null,
            'order' => $data['order'] ?? 0,
            'is_active' => array_key_exists('is_active', $data),
            'show_in_menu' => array_key_exists('show_in_menu', $data),
            'menu_group_id' => $data['menu_group_id'] ?? null,
        ]);

        $this->syncActions($module, $data['actions'] ?? []);

        return $module->load('actions');
    }

    protected function syncActions(Module $module, array $actions): void
    {
        $processedIds = [];

        foreach ($actions as $actionData) {
            if (! empty($actionData['_destroy'])) {
                continue;
            }

            if (! empty($actionData['id'])) {
                $action = ModuleAction::find($actionData['id']);

                if ($action) {
                    $action->update([
                        'name' => $actionData['name'],
                        'label' => $actionData['label'],
                        'order' => $actionData['order'],
                    ]);
                    $processedIds[] = $action->id;
                }
            } else {
                $action = ModuleAction::create([
                    'module_id' => $module->id,
                    'name' => $actionData['name'],
                    'label' => $actionData['label'],
                    'order' => $actionData['order'],
                ]);

                $processedIds[] = $action->id;

                $this->createPermissionForAction($module, $action);
                $this->grantPermissionToMasterRole($action);
            }
        }

        $module->actions()->whereNotIn('id', $processedIds)->delete();
    }

    protected function createPermissionForAction(Module $module, ModuleAction $action): void
    {
        Permission::updateOrCreate(
            ['name' => "{$module->slug}_{$action->name}"],
            [
                'module_id' => $module->id,
                'description' => "{$action->label} {$module->name}",
            ]
        );
    }

    protected function grantPermissionToMasterRole(ModuleAction $action): void
    {
        $masterRole = Role::where('name', 'Master')->first();

        if (! $masterRole) {
            return;
        }

        $permissionName = "{$action->module->slug}_{$action->name}";
        $permission = Permission::where('name', $permissionName)->first();

        if ($permission && ! $this->roleService->hasPermission($masterRole, $permissionName)) {
            $masterRole->permissions()->attach($permission->id);
        }
    }
}
