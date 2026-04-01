<?php

declare(strict_types=1);

namespace App\Http\Controllers\Acl;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleStoreRequest;
use App\Http\Requests\RoleUpdateRequest;
use App\Services\Acl\ModuleService;
use App\Services\Acl\RoleService;
use App\Services\Menu\MenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function __construct(
        protected readonly RoleService $roleService,
        protected readonly ModuleService $moduleService,
        protected readonly MenuService $menuService,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $filters = session('role_filters', [
            'name' => '',
            'sort_dir' => 'asc',
            'per_page' => 10,
        ]);

        if ($request->isMethod('POST')) {
            $filters = $this->setFilters($request);
        }

        $paginator = $this->roleService->filter($filters);

        $paginator->through(fn ($role) => [
            'uuid' => $role->uuid,
            'id' => $role->id,
            'name' => $role->name,
            'description' => $role->description,
            'is_default' => $role->is_default,
            'permissions_count' => $role->permissions->count(),
            'users_count' => $role->users->count(),
            'created_at' => $role->created_at,
        ]);

        return Inertia::render('acl/Roles/Index', [
            'roles' => $paginator,
            'filters' => $filters,
        ]);
    }

    public function setFilters(Request $request): array
    {
        $filters = [
            'name' => $request->input('name', ''),
            'sort_dir' => $request->input('sort_dir', 'asc'),
            'per_page' => $request->input('per_page', 10),
        ];

        session(['role_filters' => $filters]);

        return $filters;
    }

    public function clearFilters(): RedirectResponse
    {
        session()->forget('role_filters');

        return to_route('roles.index');
    }

    public function create(): Response
    {
        return Inertia::render('acl/Roles/Create', [
            'modules' => $this->moduleService->getFormattedForPermissions(),
        ]);
    }

    public function store(RoleStoreRequest $request): RedirectResponse
    {
        $this->roleService->create($request->validated());
        $this->menuService->clearAllMenuCache();

        return to_route('roles.index')
            ->with('success', 'Função criada com sucesso.');
    }

    public function edit(string $roleUuid): Response
    {
        $role = $this->roleService->getByUuid($roleUuid);

        return Inertia::render('acl/Roles/Edit', [
            'role' => [
                'uuid' => $role->uuid,
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'is_default' => $role->is_default,
                'permissions' => $role->permissions->map(fn ($p) => [
                    'id' => $p->id,
                    'uuid' => $p->uuid,
                ]),
            ],
            'modules' => $this->moduleService->getFormattedForPermissions(),
        ]);
    }

    public function update(RoleUpdateRequest $request, string $roleUuid): RedirectResponse
    {
        $role = $this->roleService->getByUuid($roleUuid);
        $this->roleService->update($role, $request->validated());
        $this->menuService->clearAllMenuCache();

        return to_route('roles.index')
            ->with('success', 'Função atualizada com sucesso.');
    }

    public function confirmDelete(Request $request, string $roleUuid): RedirectResponse
    {
        $role = $this->roleService->getByUuid($roleUuid);

        try {
            $this->roleService->confirmDelete(
                $role,
                $request->input('password'),
                $request->user()
            );

            $this->menuService->clearAllMenuCache();

            return to_route('roles.index')
                ->with('success', 'Função excluída com sucesso.');
        } catch (ValidationException $e) {
            throw $e;
        }
    }

    public function destroy(string $roleUuid): RedirectResponse
    {
        return $this->confirmDelete(new Request(['password' => '']), $roleUuid);
    }

    public function permissions(string $roleUuid): Response
    {
        $role = $this->roleService->getByUuid($roleUuid);

        return Inertia::render('acl/Roles/Permissions', [
            'role' => [
                'uuid' => $role->uuid,
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
                'is_default' => $role->is_default,
                'permission_ids' => $role->permissions->pluck('id'),
            ],
            'modules' => $this->moduleService->getFormattedForPermissions(),
        ]);
    }

    public function updatePermissions(Request $request, string $roleUuid): RedirectResponse
    {
        $role = $this->roleService->getByUuid($roleUuid);

        $this->roleService->updatePermissions($role, $request->input('permissions', []));
        $this->menuService->clearAllMenuCache();

        return to_route('roles.index')
            ->with('success', 'Permissões atualizadas com sucesso.');
    }
}
