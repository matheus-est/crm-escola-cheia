<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Opportunity\StoreOpportunityRequest;
use App\Http\Requests\Opportunity\UpdateOpportunityRequest;
use App\Http\Requests\Opportunity\UpdateOpportunityStatusRequest;
use App\Http\Resources\OpportunityResource;
use App\Http\Resources\OutcomeResource;
use App\Http\Resources\TaskResource;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\LeadSource;
use App\Models\Opportunity;
use App\Models\Outcome;
use App\Models\SchoolUnit;
use App\Models\SchoolYear;
use App\Models\Segment;
use App\Models\Student;
use App\Models\Task;
use App\Services\Opportunity\OpportunityService;
use Illuminate\Http\JsonResponse;
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
        $view = $request->input('view', 'kanban');

        $grades = Grade::query()->orderBy('name')->get(['uuid', 'name']);
        $schoolYears = SchoolYear::query()->orderBy('name')->get(['uuid', 'name']);
        $leadSources = LeadSource::query()->orderBy('name')->get(['uuid', 'name']);
        $responsibleUsers = $school->users()->orderBy('name')->get(['users.uuid', 'users.name']);
        $segments = Segment::query()->orderBy('name')->get(['uuid', 'name']);
        $schoolUnits = SchoolUnit::query()->orderBy('name')->get(['uuid', 'name']);

        $filters = [
            'status' => $request->input('status', ''),
            'grade_id' => $request->input('grade_id', ''),
            'school_year_id' => $request->input('school_year_id', ''),
            'responsible_user_id' => $request->input('responsible_user_id', ''),
            'lead_source_id' => $request->input('lead_source_id', ''),
            'segment_id' => $request->input('segment_id', ''),
            'school_unit_id' => $request->input('school_unit_id', ''),
            'registration_type' => $request->input('registration_type', ''),
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
            'student_cpf' => $request->input('student_cpf', ''),
            'guardian_cpf' => $request->input('guardian_cpf', ''),
        ];

        if ($view === 'kanban') {
            $kanbanColumns = $this->opportunityService->listByStatus($request->all());

            $wrappedColumns = [];
            foreach ($kanbanColumns as $status => $paginator) {
                $wrappedColumns[$status] = OpportunityResource::collection($paginator);
            }

            return Inertia::render('opportunities/Index', [
                'view' => 'kanban',
                'opportunities' => null,
                'kanban_columns' => $wrappedColumns,
                'grades' => $grades,
                'schoolYears' => $schoolYears,
                'leadSources' => $leadSources,
                'responsibleUsers' => $responsibleUsers,
                'segments' => $segments,
                'schoolUnits' => $schoolUnits,
                'filters' => $filters,
            ]);
        }

        $opportunities = $this->opportunityService->list($request->all());

        return Inertia::render('opportunities/Index', [
            'view' => 'list',
            'opportunities' => OpportunityResource::collection($opportunities),
            'kanban_columns' => null,
            'grades' => $grades,
            'schoolYears' => $schoolYears,
            'leadSources' => $leadSources,
            'responsibleUsers' => $responsibleUsers,
            'segments' => $segments,
            'schoolUnits' => $schoolUnits,
            'filters' => $filters,
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

        $opportunity->load(['student', 'guardian', 'grade', 'schoolYear', 'leadSource', 'segment', 'responsibleUser', 'schoolUnit']);

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

        $status = $opportunity->status->value;

        $stageOrder = ['cadastro_inicial' => 0, 'agendamento' => 1, 'visita' => 2, 'matricula' => 3];
        $currentOrder = $stageOrder[$status] ?? -1;

        $stages = [
            ['label' => 'Cadastro Inicial', 'slug' => 'cadastro_inicial'],
            ['label' => 'Agendamento', 'slug' => 'agendamento'],
            ['label' => 'Visita', 'slug' => 'visita'],
            ['label' => 'Matrícula', 'slug' => 'matricula'],
        ];

        if ($status === 'recusado') {
            $funnelStages = array_map(fn (array $s): array => array_merge($s, ['state' => 'pending']), $stages);
        } else {
            $funnelStages = array_map(function (array $stage) use ($stageOrder, $currentOrder, $status): array {
                $order = $stageOrder[$stage['slug']];
                $state = match (true) {
                    $order < $currentOrder => 'completed',
                    $order === $currentOrder && $status === 'matricula' => 'completed',
                    $order === $currentOrder => 'active',
                    default => 'pending',
                };

                return array_merge($stage, ['state' => $state]);
            }, $stages);
        }

        $daysInStage = (int) (($opportunity->status_changed_at ?? $opportunity->created_at)?->diffInDays(now()) ?? 0);

        $opportunityData = $opportunity->toArray();
        if (array_key_exists('guardian', $opportunityData)
            && $opportunityData['guardian'] !== null
            && array_key_exists('cpf', $opportunityData['guardian'])
            && $opportunityData['guardian']['cpf'] !== null) {
            $opportunityData['guardian']['cpf'] = $this->maskCpfLgpd($opportunityData['guardian']['cpf']);
        }

        return Inertia::render('opportunities/Show', [
            'opportunity' => $opportunityData,
            'tasks' => TaskResource::collection($tasks)->resolve(),
            'outcomes' => OutcomeResource::collection($outcomes)->resolve(),
            'users' => $users->map(fn ($u) => ['uuid' => $u->uuid, 'name' => $u->name]),
            'funnel_stages' => $funnelStages,
            'days_in_stage' => $daysInStage,
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

    public function updateStatus(UpdateOpportunityStatusRequest $request, Opportunity $opportunity): JsonResponse
    {
        Gate::authorize('update', $opportunity);
        $opportunity->update(['status' => $request->validated()['status']]);

        return response()->json(['status' => $opportunity->status]);
    }

    public function destroy(Opportunity $opportunity): RedirectResponse
    {
        Gate::authorize('delete', $opportunity);

        $opportunity->delete();

        return to_route('tenant.opportunities.index')
            ->with('success', 'Oportunidade excluída com sucesso.');
    }

    private function maskCpfLgpd(string $cpf): string
    {
        // Input: "123.456.789-00"  →  Output: "***.456.789-**"
        return preg_replace('/^\d{3}(\.\d{3}\.\d{3}-)\d{2}$/', '***$1**', $cpf) ?? $cpf;
    }
}
