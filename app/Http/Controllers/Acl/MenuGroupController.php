<?php

declare(strict_types=1);

namespace App\Http\Controllers\Acl;

use App\Http\Controllers\Controller;
use App\Http\Requests\MenuGroupRequest;
use App\Services\Menu\MenuGroupService;
use App\Services\Menu\MenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MenuGroupController extends Controller
{
    public function __construct(
        protected readonly MenuGroupService $menuGroupService,
        protected readonly MenuService $menuService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = session('menu_group_filters', [
            'per_page' => 10,
        ]);

        if ($request->isMethod('POST')) {
            $filters = $this->setFilters($request);
        }

        $paginator = $this->menuGroupService->filter($filters);

        return Inertia::render('acl/MenuGroups/Index', [
            'menuGroups' => $paginator,
            'filters' => $filters,
        ]);
    }

    public function setFilters(Request $request): array
    {
        $filters = [
            'per_page' => $request->input('per_page', 10),
        ];

        session(['menu_group_filters' => $filters]);

        return $filters;
    }

    public function create(): Response
    {
        return Inertia::render('acl/MenuGroups/Create');
    }

    public function store(MenuGroupRequest $request): RedirectResponse
    {
        $this->menuGroupService->create($request->validated());
        $this->menuService->clearAllMenuCache();

        return to_route('menu-groups.index')
            ->with('success', 'Grupo de menu criado com sucesso.');
    }

    public function edit(string $menuGroupUuid): Response
    {
        $menuGroup = $this->menuGroupService->getByUuid($menuGroupUuid);

        return Inertia::render('acl/MenuGroups/Edit', [
            'menuGroup' => [
                'uuid' => $menuGroup->uuid,
                'id' => $menuGroup->id,
                'name' => $menuGroup->name,
                'slug' => $menuGroup->slug,
                'icon' => $menuGroup->icon,
                'order' => $menuGroup->order,
                'is_active' => $menuGroup->is_active,
            ],
        ]);
    }

    public function update(MenuGroupRequest $request, string $menuGroupUuid): RedirectResponse
    {
        $menuGroup = $this->menuGroupService->getByUuid($menuGroupUuid);

        $this->menuGroupService->update($menuGroup, $request->validated());
        $this->menuService->clearAllMenuCache();

        return to_route('menu-groups.index')
            ->with('success', 'Grupo de menu atualizado com sucesso.');
    }

    public function destroy(string $menuGroupUuid): RedirectResponse
    {
        $menuGroup = $this->menuGroupService->getByUuid($menuGroupUuid);

        $this->menuGroupService->delete($menuGroup);
        $this->menuService->clearAllMenuCache();

        return to_route('menu-groups.index')
            ->with('success', 'Grupo de menu excluído com sucesso.');
    }
}
