<?php

declare(strict_types=1);

namespace App\Services\Menu;

use App\Models\MenuGroup;
use App\Models\Module;
use App\Models\User;
use Illuminate\Support\Collection;

class MenuService
{
    protected const CACHE_PREFIX = 'user_menu_';

    protected const CACHE_TTL = 3600;

    public function getMenuForUser(User $user): Collection
    {
        if (! $user->role) {
            return collect();
        }

        $cacheKey = self::CACHE_PREFIX.$user->role_id;

        $keys = cache()->get('menu_cache_keys', []);
        if (! in_array($cacheKey, $keys)) {
            $keys[] = $cacheKey;
            cache()->put('menu_cache_keys', $keys);
        }

        $cached = cache()->remember($cacheKey, self::CACHE_TTL, function () use ($user) {
            return $this->buildMenu($user)->toArray();
        });

        return collect($cached);
    }

    public function clearMenuCacheForRole(int $roleId): void
    {
        cache()->forget(self::CACHE_PREFIX.$roleId);
    }

    public function clearAllMenuCache(): void
    {
        $keys = cache()->get('menu_cache_keys', []);

        foreach ($keys as $key) {
            cache()->forget($key);
        }

        cache()->forget('menu_cache_keys');
    }

    protected function buildMenu(User $user): Collection
    {
        $user->loadMissing(['role', 'role.permissions']);

        $permissionNames = $user->role?->permissions->pluck('name') ?? collect();

        $modules = Module::query()
            ->where('show_in_menu', true)
            ->where('is_active', true)
            ->orderBy('order')
            ->get()
            ->filter(function (Module $module) use ($permissionNames) {
                $requiredPermission = $module->slug.'_list';

                if ($module->slug === 'dashboard') {
                    return $permissionNames->contains('dashboard_view');
                }

                return $permissionNames->contains($requiredPermission);
            })
            ->map(function (Module $module) use ($permissionNames) {
                return [
                    'id' => $module->id,
                    'name' => $module->name,
                    'slug' => $module->slug,
                    'icon' => $module->icon,
                    'url' => $module->url,
                    'menu_group_id' => $module->menu_group_id,
                    'order' => $module->order,
                    'permissions' => $this->getModulePermissions($module, $permissionNames),
                ];
            });

        return $this->groupModules($modules);
    }

    protected function groupModules(Collection $modules): Collection
    {
        $dashboard = $modules->first(fn ($m) => $m['slug'] === 'dashboard');

        $withoutGroup = $modules
            ->filter(fn ($m) => $m['menu_group_id'] === null && $m['slug'] !== 'dashboard')
            ->sortBy('order')
            ->values();

        $groups = MenuGroup::query()
            ->where('is_active', true)
            ->orderBy('order')
            ->get()
            ->map(function (MenuGroup $group) use ($modules) {
                $groupModules = $modules->filter(fn ($m) => $m['menu_group_id'] === $group->id);

                if ($groupModules->isEmpty()) {
                    return null;
                }

                return [
                    'id' => $group->id,
                    'uuid' => $group->uuid,
                    'name' => $group->name,
                    'slug' => $group->slug,
                    'icon' => $group->icon,
                    'order' => $group->slug === 'configuration' ? PHP_INT_MAX : $group->order,
                    'items' => $groupModules->sortBy('order')->values()->toArray(),
                ];
            })
            ->filter()
            ->sortBy('order')
            ->values();

        $result = collect();

        if ($dashboard) {
            $result->push([
                'id' => $dashboard['id'],
                'uuid' => $dashboard['id'],
                'name' => $dashboard['name'],
                'slug' => $dashboard['slug'],
                'icon' => $dashboard['icon'],
                'url' => $dashboard['url'],
                'order' => 0,
            ]);
        }

        foreach ($withoutGroup as $item) {
            $result->push([
                'id' => $item['id'],
                'uuid' => $item['slug'],
                'name' => $item['name'],
                'slug' => $item['slug'],
                'icon' => $item['icon'],
                'url' => $item['url'],
                'order' => $item['order'],
            ]);
        }

        foreach ($groups as $group) {
            $result->push([
                'id' => $group['id'],
                'uuid' => $group['uuid'],
                'name' => $group['name'],
                'slug' => $group['slug'],
                'icon' => $group['icon'],
                'order' => $group['order'],
                'items' => $group['items'],
            ]);
        }

        return $result->values();
    }

    protected function getModulePermissions(Module $module, Collection $permissionNames): array
    {
        $permissions = [];

        foreach (['list', 'add', 'edit', 'view', 'delete'] as $action) {
            $permissions[$action] = $permissionNames->contains("{$module->slug}_{$action}");
        }

        return $permissions;
    }
}
