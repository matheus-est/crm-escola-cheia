<?php

declare(strict_types=1);

namespace App\Http\Controllers\Acl;

use App\Http\Controllers\Controller;
use App\Services\Acl\ModuleService;
use App\Services\Menu\MenuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ModuleController extends Controller
{
    public function __construct(
        protected readonly ModuleService $moduleService,
        protected readonly MenuService $menuService,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $filters = session('module_filters', [
            'per_page' => 10,
        ]);

        if ($request->isMethod('POST')) {
            $filters = $this->setFilters($request);
        }

        $paginator = $this->moduleService->filterForUser($request->user(), $filters);

        return Inertia::render('acl/Modules/Index', [
            'modules' => $paginator,
            'filters' => $filters,
        ]);
    }

    public function setFilters(Request $request): array
    {
        $filters = [
            'per_page' => $request->input('per_page', 10),
        ];

        session(['module_filters' => $filters]);

        return $filters;
    }

    public function edit(string $moduleUuid): Response
    {
        $module = $this->moduleService->getByUuid($moduleUuid);
        $menuGroups = $this->moduleService->getMenuGroups();

        return Inertia::render('acl/Modules/Edit', [
            'module' => [
                'uuid' => $module->uuid,
                'id' => $module->id,
                'name' => $module->name,
                'slug' => $module->slug,
                'icon' => $module->icon,
                'url' => $module->url,
                'description' => $module->description,
                'is_active' => $module->is_active,
                'show_in_menu' => $module->show_in_menu,
                'order' => $module->order,
                'menu_group_id' => $module->menu_group_id,
                'actions' => $module->actions->map(fn ($action) => [
                    'uuid' => $action->uuid,
                    'id' => $action->id,
                    'name' => $action->name,
                    'label' => $action->label,
                    'order' => $action->order,
                ])->toArray(),
            ],
            'menuGroups' => $menuGroups,
        ]);
    }

    public function update(Request $request, string $moduleUuid): RedirectResponse
    {
        $module = $this->moduleService->getByUuid($moduleUuid);

        $data = $request->all();
        $data['menu_group_id'] = $request->input('menu_group_id') ?: null;
        $data['actions'] = $this->parseActions($request);

        $this->moduleService->update($module, $data);
        $this->menuService->clearAllMenuCache();

        return to_route('modules.index')
            ->with('success', 'Módulo atualizado com sucesso.');
    }

    protected function parseActions(Request $request): array
    {
        $actions = [];
        $i = 0;

        while ($request->has("actions.{$i}.name")) {
            $actions[] = [
                'id' => $request->input("actions.{$i}.id") ? (int) $request->input("actions.{$i}.id") : null,
                'name' => $request->input("actions.{$i}.name"),
                'label' => $request->input("actions.{$i}.label"),
                'order' => (int) $request->input("actions.{$i}.order"),
                '_destroy' => $request->input("actions.{$i}._destroy") === '1'
                    || $request->input("actions.{$i}._destroy") === true,
            ];
            $i++;
        }

        return $actions;
    }
}
