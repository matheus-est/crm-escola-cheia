<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Opportunity\StoreOpportunityRequest;
use App\Http\Requests\Opportunity\UpdateOpportunityRequest;
use App\Http\Resources\OutcomeResource;
use App\Http\Resources\TaskResource;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\LeadSource;
use App\Models\Opportunity;
use App\Models\Outcome;
use App\Models\SchoolYear;
use App\Models\Segment;
use App\Models\Student;
use App\Models\Task;
use App\Services\Opportunity\OpportunityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        $school = Auth::user()->currentSchool();

        $opportunities = $this->opportunityService->list($request->all());

        $opportunities->load([
            'student',
            'guardian',
            'grade',
            'schoolYear',
            'responsibleUser',
        ]);

        $grades = Grade::query()->orderBy('name')->get();
        $schoolYears = SchoolYear::query()->orderBy('name')->get();
        $leadSources = LeadSource::query()->orderBy('name')->get();
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

        $school = Auth::user()->currentSchool();

        $grades = Grade::query()->orderBy('name')->get();
        $schoolYears = SchoolYear::query()->orderBy('name')->get();
        $leadSources = LeadSource::query()->orderBy('name')->get();
        $users = $school->users()->orderBy('name')->get();
        $segments = Segment::query()->orderBy('name')->get();

        return Inertia::render('opportunities/Create', [
            'school' => $school,
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

    public function show(Opportunity $opportunity): Response
    {
        Gate::authorize('view', $opportunity);

        $tasks = Task::query()
            ->where('opportunity_id', $opportunity->id)
            ->with(['assignedUser', 'outcome'])
            ->orderByDesc('created_at')
            ->get();

        $outcomes = Outcome::query()
            ->orderBy('task_type')
            ->orderBy('name')
            ->get();

        $school = Auth::user()->currentSchool();
        $users = $school->users()->orderBy('name')->get();

        return Inertia::render('opportunities/Show', [
            'opportunity' => $opportunity->load(['student', 'guardian', 'grade', 'schoolYear', 'leadSource', 'segment', 'responsibleUser']),
            'tasks' => TaskResource::collection($tasks),
            'outcomes' => OutcomeResource::collection($outcomes),
            'users' => $users->map(fn ($u) => ['id' => $u->id, 'uuid' => $u->uuid, 'name' => $u->name]),
        ]);
    }

    public function edit(Opportunity $opportunity): Response
    {
        Gate::authorize('update', $opportunity);

        $school = Auth::user()->currentSchool();

        $opportunity->load([
            'student',
            'guardian',
            'grade',
            'schoolYear',
            'leadSource',
            'responsibleUser',
            'segment',
        ]);

        $students = Student::query()->orderBy('name')->get();
        $guardians = Guardian::query()->orderBy('name')->get();
        $grades = Grade::query()->orderBy('name')->get();
        $schoolYears = SchoolYear::query()->orderBy('name')->get();
        $leadSources = LeadSource::query()->orderBy('name')->get();
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
