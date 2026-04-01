<?php

declare(strict_types=1);

namespace App\Services\Acl;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class RoleService
{
    /**
     * Returns all roles for internal/select use only (not a paginated UI listing).
     * Limited to 500 records to prevent OOM.
     */
    public function getAll(): Collection
    {
        return Role::with(['permissions', 'users'])->limit(500)->get();
    }

    public function getById(int $id): Role
    {
        return Role::with(['permissions', 'users'])->findOrFail($id);
    }

    public function getByUuid(string $uuid): Role
    {
        return Role::with(['permissions', 'users'])->where('uuid', $uuid)->firstOrFail();
    }

    public function getRestriction(): Collection
    {
        $currentUser = request()->user();
        $isMaster = $currentUser?->role?->name === 'Master';

        return Role::orderBy('name')
            ->when(! $isMaster, fn ($q) => $q->where('name', '!=', 'Master'))
            ->get(['id', 'uuid', 'name']);
    }

    public function filter(array $filters): LengthAwarePaginator
    {
        $sortDir = ($filters['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = $filters['per_page'] ?? 10;
        $perPage = $perPage === 'all' || (int) $perPage === 0
            ? PHP_INT_MAX
            : (int) $perPage;

        return Role::with(['permissions', 'users'])
            ->when(
                ! empty($filters['name']),
                fn ($q) => $q->where('name', 'like', '%'.$filters['name'].'%')
            )
            ->orderBy('name', $sortDir)
            ->paginate($perPage);
    }

    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            if (! empty($data['permissions'])) {
                $this->updatePermissions($role, $data['permissions']);
            }

            return $role->load('permissions');
        });
    }

    public function update(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data) {
            $role->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_default' => $data['is_default'] ?? false,
            ]);

            if (array_key_exists('permissions', $data)) {
                $this->updatePermissions($role, $data['permissions']);
            }

            return $role->load('permissions');
        });
    }

    /**
     * Update role permissions using attach/detach diff instead of sync.
     *
     * @param  array<int>  $permissionIds
     */
    public function updatePermissions(Role $role, array $permissionIds): void
    {
        $current = $role->permissions()->pluck('permissions.id')->toArray();
        $toAttach = array_diff($permissionIds, $current);
        $toDetach = array_diff($current, $permissionIds);

        if (! empty($toAttach)) {
            $role->permissions()->attach($toAttach);
        }

        if (! empty($toDetach)) {
            $role->permissions()->detach($toDetach);
        }
    }

    /**
     * Returns all modules that have at least one permission assigned to the given role.
     */
    public function getModulesForRole(Role $role): Collection
    {
        return Module::query()
            ->whereHas('permissions', function (Builder $query) use ($role) {
                $query->whereIn('permissions.id', $role->permissions->pluck('id'));
            })
            ->orderBy('order')
            ->get();
    }

    public function delete(Role $role): void
    {
        if ($role->users()->count() > 0) {
            throw new \Exception('Não é possível excluir. Existem usuários vinculados a esta função.');
        }

        DB::transaction(function () use ($role) {
            $role->permissions()->detach();
            $role->delete();
        });
    }

    public function confirmDelete(Role $role, string $password, User $currentUser): bool
    {
        if (! Hash::check($password, $currentUser->password)) {
            $validator = Validator::make(['password' => $password], ['password' => 'required']);
            $validator->errors()->add('password', 'Senha incorreta.');
            throw new ValidationException($validator);
        }

        $this->delete($role);

        return true;
    }

    public function hasPermission(Role $role, string $permission): bool
    {
        return $role->permissions()->where('name', $permission)->exists();
    }

    public function givePermission(Role $role, string $permission): void
    {
        $permissionModel = Permission::query()->where('name', $permission)->firstOrFail();

        if (! $this->hasPermission($role, $permission)) {
            $role->permissions()->attach($permissionModel);
        }
    }

    public function revokePermission(Role $role, string $permission): void
    {
        $permissionModel = Permission::query()->where('name', $permission)->firstOrFail();
        $role->permissions()->detach($permissionModel);
    }
}
