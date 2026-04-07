<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Opportunity\StoreOpportunityRequest;
use App\Http\Requests\Opportunity\UpdateOpportunityRequest;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\LeadSource;
use App\Models\Opportunity;
use App\Models\SchoolYear;
use App\Models\Segment;
use App\Models\Student;
use App\Services\Opportunity\OpportunityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OpportunityController extends Controller
{
    public function __construct(
        protected readonly OpportunityService $opportunityService,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Opportunity::class);

        $school = auth()->user()->currentSchool();

        $opportunities = $this->opportunityService->list($request->all());

        $grades = Grade::query()->orderBy('nome')->get();
        $schoolYears = SchoolYear::query()->orderBy('nome')->get();
        $leadSources = LeadSource::query()->orderBy('nome')->get();
        $responsibleUsers = $school->users()->orderBy('name')->get();
        $segments = Segment::query()->orderBy('name')->get();

        return Inertia::render('opportunities/Index', [
            'school' => $school,
            'opportunities' => $opportunities,
            'grades' => $grades,
            'schoolYears' => $schoolYears,
            'leadSources' => $leadSources,
            'responsibleUsers' => $responsibleUsers,
            'segments' => $segments,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Opportunity::class);

        $school = auth()->user()->currentSchool();

        $students = Student::query()->orderBy('nome')->get();
        $guardians = Guardian::query()->orderBy('nome')->get();
        $grades = Grade::query()->orderBy('nome')->get();
        $schoolYears = SchoolYear::query()->orderBy('nome')->get();
        $leadSources = LeadSource::query()->orderBy('nome')->get();
        $users = $school->users()->orderBy('name')->get();
        $segments = Segment::query()->orderBy('name')->get();

        return Inertia::render('opportunities/Create', [
            'school' => $school,
            'students' => $students,
            'guardians' => $guardians,
            'grades' => $grades,
            'schoolYears' => $schoolYears,
            'leadSources' => $leadSources,
            'users' => $users,
            'segments' => $segments,
        ]);
    }

    public function store(StoreOpportunityRequest $request): RedirectResponse
    {
        Gate::authorize('create', Opportunity::class);

        $validated = $request->validated();

        $this->opportunityService->create($validated);

        $flashKey = $this->opportunityService->hasClosedSchoolYear($validated)
            ? 'warning'
            : 'success';

        $flashMessage = $flashKey === 'warning'
            ? 'Oportunidade criada, mas o ano letivo selecionado está encerrado.'
            : 'Oportunidade criada com sucesso.';

        return to_route('tenant.opportunities.index')
            ->with($flashKey, $flashMessage);
    }

    public function edit(Opportunity $opportunity): Response
    {
        Gate::authorize('update', $opportunity);

        $school = auth()->user()->currentSchool();

        $students = Student::query()->orderBy('nome')->get();
        $guardians = Guardian::query()->orderBy('nome')->get();
        $grades = Grade::query()->orderBy('nome')->get();
        $schoolYears = SchoolYear::query()->orderBy('nome')->get();
        $leadSources = LeadSource::query()->orderBy('nome')->get();
        $users = $school->users()->orderBy('name')->get();
        $segments = Segment::query()->orderBy('name')->get();

        return Inertia::render('opportunities/Edit', [
            'school' => $school,
            'opportunity' => $opportunity,
            'students' => $students,
            'guardians' => $guardians,
            'grades' => $grades,
            'schoolYears' => $schoolYears,
            'leadSources' => $leadSources,
            'users' => $users,
            'segments' => $segments,
        ]);
    }

    public function update(UpdateOpportunityRequest $request, Opportunity $opportunity): RedirectResponse
    {
        Gate::authorize('update', $opportunity);

        $this->opportunityService->update($opportunity, $request->validated());

        return to_route('tenant.opportunities.index')
            ->with('success', 'Oportunidade atualizada com sucesso.');
    }

    public function destroy(Opportunity $opportunity): RedirectResponse
    {
        Gate::authorize('delete', $opportunity);

        $opportunity->delete();

        return to_route('tenant.opportunities.index')
            ->with('success', 'Oportunidade excluída com sucesso.');
    }
}
