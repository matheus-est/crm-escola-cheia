<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\LeadSource\LeadSourceStoreRequest;
use App\Http\Requests\LeadSource\LeadSourceUpdateRequest;
use App\Models\LeadSource;
use App\Services\LeadSource\LeadSourceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LeadSourceController extends Controller
{
    public function __construct(
        protected readonly LeadSourceService $leadSourceService,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', LeadSource::class);

        $filters = [
            'name' => $request->input('name', ''),
            'sort_by' => $request->input('sort_by', 'name'),
            'sort_dir' => $request->input('sort_dir', 'asc'),
            'per_page' => $request->input('per_page', 10),
        ];

        $leadSources = $this->leadSourceService->list($filters);

        return Inertia::render('tenant-settings/LeadSources', [
            'school' => auth()->user()->currentSchool(),
            'leadSources' => $leadSources,
            'filters' => $filters,
        ]);
    }

    public function store(LeadSourceStoreRequest $request): RedirectResponse
    {
        Gate::authorize('create', LeadSource::class);

        $this->leadSourceService->create($request->validated());

        return to_route('tenant.lead_sources.index')
            ->with('success', 'Origem de lead criada com sucesso.');
    }

    public function update(LeadSourceUpdateRequest $request, LeadSource $leadSource): RedirectResponse
    {
        Gate::authorize('update', $leadSource);

        $this->leadSourceService->update($leadSource, $request->validated());

        return to_route('tenant.lead_sources.index')
            ->with('success', 'Origem de lead atualizada com sucesso.');
    }

    public function destroy(LeadSource $leadSource): RedirectResponse
    {
        Gate::authorize('delete', $leadSource);

        $this->leadSourceService->destroy($leadSource);

        return to_route('tenant.lead_sources.index')
            ->with('success', 'Origem de lead excluída com sucesso.');
    }
}
