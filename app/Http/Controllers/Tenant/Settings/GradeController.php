<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Settings\StoreGradeRequest;
use App\Http\Requests\Tenant\Settings\UpdateGradeRequest;
use App\Models\Grade;
use App\Models\Segment;
use App\Services\Grade\GradeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GradeController extends Controller
{
    public function __construct(
        protected readonly GradeService $gradeService,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Grade::class);

        $filters = [
            'name' => $request->input('name', ''),
            'segment_id' => $request->input('segment_id', ''),
            'sort_by' => $request->input('sort_by', 'name'),
            'sort_dir' => $request->input('sort_dir', 'asc'),
            'per_page' => $request->input('per_page', 10),
        ];

        $grades = $this->gradeService->list($filters);

        $segments = Segment::orderBy('name')->get(['uuid', 'name']);

        return Inertia::render('tenant-settings/Grades', [
            'school' => auth()->user()->currentSchool(),
            'grades' => $grades,
            'segments' => $segments,
            'filters' => $filters,
        ]);
    }

    public function store(StoreGradeRequest $request): RedirectResponse
    {
        Gate::authorize('create', Grade::class);

        $this->gradeService->create($request->validated());

        return to_route('tenant.grades.index')
            ->with('success', 'Turma criada com sucesso.');
    }

    public function update(UpdateGradeRequest $request, Grade $grade): RedirectResponse
    {
        Gate::authorize('update', $grade);

        $this->gradeService->update($grade, $request->validated());

        return to_route('tenant.grades.index')
            ->with('success', 'Turma atualizada com sucesso.');
    }

    public function destroy(Grade $grade): RedirectResponse
    {
        Gate::authorize('delete', $grade);

        $this->gradeService->destroy($grade);

        return to_route('tenant.grades.index')
            ->with('success', 'Turma excluída com sucesso.');
    }
}
