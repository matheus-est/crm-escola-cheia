<?php

declare(strict_types=1);

namespace App\Services\Acl;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Collection;

class PermissionService
{
    public function getAll(): Collection
    {
        return Permission::with('module')->orderBy('name')->get();
    }

    public function getById(int $id): Permission
    {
        return Permission::with('module')->findOrFail($id);
    }

    public function getByModule(int $moduleId): Collection
    {
        return Permission::byModule($moduleId)->get();
    }

    public function getModules(): Collection
    {
        return Module::active()->orderBy('order')->get();
    }

    public function create(array $data): Permission
    {
        return Permission::create([
            'name' => $data['name'],
            'module_id' => $data['module_id'],
            'description' => $data['description'] ?? null,
        ]);
    }

    public function update(Permission $permission, array $data): Permission
    {
        $permission->update([
            'name' => $data['name'],
            'module_id' => $data['module_id'],
            'description' => $data['description'] ?? null,
        ]);

        return $permission;
    }

    public function delete(Permission $permission): void
    {
        $permission->profiles()->detach();
        $permission->delete();
    }
}
