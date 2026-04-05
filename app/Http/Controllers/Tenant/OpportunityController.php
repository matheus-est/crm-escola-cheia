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
use App\Models\School;
use App\Models\SchoolYear;
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

    public function index(Request $request, School $school): Response
    {
        Gate::authorize('viewAny', Opportunity::class);

        $opportunities = $this->opportunityService->list($school, $request->all());

        $grades = Grade::query()->where('school_id', $school->id)->orderBy('nome')->get();
        $schoolYears = SchoolYear::query()->where('school_id', $school->id)->orderBy('nome')->get();
        $leadSources = LeadSource::query()
            ->where(fn ($q) => $q->where('school_id', $school->id)->orWhereNull('school_id'))
            ->orderBy('nome')
            ->get();
        $responsibleUsers = $school->users()->orderBy('name')->get();

        return Inertia::render('opportunities/Index', [
            'school' => $school,
            'opportunities' => $opportunities,
            'grades' => $grades,
            'schoolYears' => $schoolYears,
            'leadSources' => $leadSources,
            'responsibleUsers' => $responsibleUsers,
        ]);
    }

    public function create(School $school): Response
    {
        Gate::authorize('create', Opportunity::class);

        $students = Student::query()->where('school_id', $school->id)->orderBy('nome')->get();
        $guardians = Guardian::query()->where('school_id', $school->id)->orderBy('nome')->get();
        $grades = Grade::query()->where('school_id', $school->id)->orderBy('nome')->get();
        $schoolYears = SchoolYear::query()->where('school_id', $school->id)->orderBy('nome')->get();
        $leadSources = LeadSource::query()
            ->where(fn ($q) => $q->where('school_id', $school->id)->orWhereNull('school_id'))
            ->orderBy('nome')
            ->get();
        $users = $school->users()->orderBy('name')->get();

        return Inertia::render('opportunities/Create', [
            'school' => $school,
            'students' => $students,
            'guardians' => $guardians,
            'grades' => $grades,
            'schoolYears' => $schoolYears,
            'leadSources' => $leadSources,
            'users' => $users,
        ]);
    }

    public function store(StoreOpportunityRequest $request, School $school): RedirectResponse
    {
        Gate::authorize('create', Opportunity::class);

        $validated = $request->validated();

        $this->opportunityService->create($school, $validated);

        $flashKey = $this->opportunityService->hasClosedSchoolYear($validated)
            ? 'warning'
            : 'success';

        $flashMessage = $flashKey === 'warning'
            ? 'Oportunidade criada, mas o ano letivo selecionado está encerrado.'
            : 'Oportunidade criada com sucesso.';

        return to_route('tenant.opportunities.index', $school)
            ->with($flashKey, $flashMessage);
    }

    public function edit(School $school, Opportunity $opportunity): Response
    {
        Gate::authorize('update', $opportunity);

        $students = Student::query()->where('school_id', $school->id)->orderBy('nome')->get();
        $guardians = Guardian::query()->where('school_id', $school->id)->orderBy('nome')->get();
        $grades = Grade::query()->where('school_id', $school->id)->orderBy('nome')->get();
        $schoolYears = SchoolYear::query()->where('school_id', $school->id)->orderBy('nome')->get();
        $leadSources = LeadSource::query()
            ->where(fn ($q) => $q->where('school_id', $school->id)->orWhereNull('school_id'))
            ->orderBy('nome')
            ->get();
        $users = $school->users()->orderBy('name')->get();

        return Inertia::render('opportunities/Edit', [
            'school' => $school,
            'opportunity' => $opportunity,
            'students' => $students,
            'guardians' => $guardians,
            'grades' => $grades,
            'schoolYears' => $schoolYears,
            'leadSources' => $leadSources,
            'users' => $users,
        ]);
    }

    public function update(UpdateOpportunityRequest $request, School $school, Opportunity $opportunity): RedirectResponse
    {
        Gate::authorize('update', $opportunity);

        $this->opportunityService->update($opportunity, $request->validated());

        return to_route('tenant.opportunities.index', $school)
            ->with('success', 'Oportunidade atualizada com sucesso.');
    }

    public function destroy(School $school, Opportunity $opportunity): RedirectResponse
    {
        Gate::authorize('delete', $opportunity);

        $opportunity->delete();

        return to_route('tenant.opportunities.index', $school)
            ->with('success', 'Oportunidade excluída com sucesso.');
    }
}
