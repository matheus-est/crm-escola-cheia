<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\SchoolYear\SchoolYearStoreRequest;
use App\Http\Requests\SchoolYear\SchoolYearUpdateRequest;
use App\Models\School;
use App\Models\SchoolYear;
use App\Services\SchoolYear\SchoolYearService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SchoolYearController extends Controller
{
    public function __construct(
        protected readonly SchoolYearService $schoolYearService,
    ) {}

    public function index(Request $request, School $school): Response
    {
        Gate::authorize('viewAny', SchoolYear::class);

        $filters = [
            'nome' => $request->input('nome', ''),
            'status' => $request->input('status', ''),
            'sort_by' => $request->input('sort_by', 'nome'),
            'sort_dir' => $request->input('sort_dir', 'asc'),
            'per_page' => $request->input('per_page', 10),
        ];

        $schoolYears = $this->schoolYearService->list($school, $filters);

        return Inertia::render('tenant-settings/SchoolYears', [
            'school' => $school,
            'schoolYears' => $schoolYears,
            'filters' => $filters,
        ]);
    }

    public function store(SchoolYearStoreRequest $request, School $school): RedirectResponse
    {
        Gate::authorize('create', SchoolYear::class);

        $this->schoolYearService->create($school, $request->validated());

        return to_route('tenant.school_years.index', $school)
            ->with('success', 'Ano letivo criado com sucesso.');
    }

    public function update(SchoolYearUpdateRequest $request, School $school, SchoolYear $schoolYear): RedirectResponse
    {
        Gate::authorize('update', $schoolYear);

        $this->schoolYearService->update($schoolYear, $request->validated());

        return to_route('tenant.school_years.index', $school)
            ->with('success', 'Ano letivo atualizado com sucesso.');
    }

    public function destroy(School $school, SchoolYear $schoolYear): RedirectResponse
    {
        Gate::authorize('delete', $schoolYear);

        $this->schoolYearService->destroy($schoolYear);

        return to_route('tenant.school_years.index', $school)
            ->with('success', 'Ano letivo excluído com sucesso.');
    }
}
