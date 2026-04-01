<?php

declare(strict_types=1);

namespace App\Services\Menu;

use App\Models\MenuGroup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class MenuGroupService
{
    public function getAll(): Collection
    {
        return MenuGroup::orderByRaw("CASE WHEN slug = 'configuration' THEN 1 ELSE 0 END")
            ->orderBy('order')
            ->get();
    }

    public function getById(int $id): MenuGroup
    {
        return MenuGroup::findOrFail($id);
    }

    public function getByUuid(string $uuid): MenuGroup
    {
        return MenuGroup::where('uuid', $uuid)->firstOrFail();
    }

    public function filter(array $filters): LengthAwarePaginator
    {
        $sortDir = ($filters['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = $filters['per_page'] ?? 10;
        $perPage = $perPage === 'all' || (int) $perPage === 0
            ? PHP_INT_MAX
            : (int) $perPage;

        return MenuGroup::withCount('modules')
            ->when(
                ! empty($filters['name']),
                fn ($q) => $q->where('name', 'like', '%'.$filters['name'].'%')
            )
            ->orderByRaw("CASE WHEN slug = 'configuration' THEN 1 ELSE 0 END")
            ->orderBy('order', $sortDir)
            ->paginate($perPage);
    }

    public function create(array $data): MenuGroup
    {
        return DB::transaction(function () use ($data) {
            return MenuGroup::create([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'icon' => $data['icon'] ?? null,
                'order' => $data['order'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);
        });
    }

    public function update(MenuGroup $menuGroup, array $data): MenuGroup
    {
        return DB::transaction(function () use ($menuGroup, $data) {
            $menuGroup->update([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'icon' => $data['icon'] ?? null,
                'order' => $data['order'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);

            return $menuGroup;
        });
    }

    public function delete(MenuGroup $menuGroup): void
    {
        DB::transaction(function () use ($menuGroup) {
            $menuGroup->modules()->update(['menu_group_id' => null]);
            $menuGroup->delete();
        });
    }
}
