<?php

declare(strict_types=1);

namespace App\Http\Controllers\Acl;

use App\Http\Controllers\Controller;
use App\Http\Requests\PermissionStoreRequest;
use App\Http\Requests\PermissionUpdateRequest;
use App\Models\Module;
use App\Services\Acl\PermissionService;
use App\Services\Menu\MenuService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PermissionController extends Controller
{
    public function __construct(
        protected readonly PermissionService $permissionService,
        protected readonly MenuService $menuService,
    ) {}

    public function index(): Response
    {
        $permissions = $this->permissionService->getAll()->map(function ($p) {
            return [
                'id' => $p->id,
                'name' => $p->name,
                'description' => $p->description,
                'module' => [
                    'id' => $p->module->id,
                    'name' => $p->module->name,
                ],
                'created_at' => $p->created_at,
            ];
        });

        return Inertia::render('acl/Permissions/Index', [
            'permissions' => $permissions,
        ]);
    }

    public function create(): Response
    {
        $modules = Module::orderBy('name')->get(['id', 'name', 'slug']);

        return Inertia::render('acl/Permissions/Create', [
            'modules' => $modules,
        ]);
    }

    public function store(PermissionStoreRequest $request): RedirectResponse
    {
        $this->permissionService->create($request->validated());
        $this->menuService->clearAllMenuCache();

        return to_route('permissions.index')
            ->with('success', 'Permissão criada com sucesso.');
    }

    public function edit(int $permission): Response
    {
        $permission = $this->permissionService->getById($permission);
        $modules = Module::orderBy('name')->get(['id', 'name', 'slug']);

        return Inertia::render('acl/Permissions/Edit', [
            'permission' => [
                'id' => $permission->id,
                'name' => $permission->name,
                'description' => $permission->description,
                'module_id' => $permission->module_id,
            ],
            'modules' => $modules,
        ]);
    }

    public function update(PermissionUpdateRequest $request, int $permission): RedirectResponse
    {
        $permission = $this->permissionService->getById($permission);
        $this->permissionService->update($permission, $request->validated());
        $this->menuService->clearAllMenuCache();

        return to_route('permissions.index')
            ->with('success', 'Permissão atualizada com sucesso.');
    }

    public function destroy(int $permission): RedirectResponse
    {
        $permission = $this->permissionService->getById($permission);
        $this->permissionService->delete($permission);
        $this->menuService->clearAllMenuCache();

        return to_route('permissions.index')
            ->with('success', 'Permissão excluída com sucesso.');
    }
}
