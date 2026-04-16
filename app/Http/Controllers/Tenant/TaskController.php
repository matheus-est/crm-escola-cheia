<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Task\CompleteTaskRequest;
use App\Http\Requests\Task\StoreTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Opportunity;
use App\Models\Outcome;
use App\Models\Task;
use App\Models\User;
use App\Services\Task\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TaskController extends Controller
{
    public function __construct(
        protected readonly TaskService $taskService,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Task::class);

        $school = Auth::user()->currentSchool();
        $users = $school->users()->orderBy('name')->get(['users.uuid', 'users.name']);

        $hasParams = $request->hasAny([
            'status', 'type', 'assigned_user_uuid', 'date_from', 'date_to', 'is_schedule', 'opportunity_uuid',
        ]);

        if ($request->isMethod('post') || $hasParams) {
            $filters = $this->setFilters($request);
        } else {
            $stored = session('task_filters', []);
            $filters = [
                'opportunity_uuid' => $stored['opportunity_uuid'] ?? '',
                'status' => $stored['status'] ?? '',
                'type' => $stored['type'] ?? '',
                'assigned_user_uuid' => $stored['assigned_user_uuid'] ?? '',
                'assigned_user_id' => $stored['assigned_user_id'] ?? null,
                'date_from' => $stored['date_from'] ?? '',
                'date_to' => $stored['date_to'] ?? '',
                'is_schedule' => $stored['is_schedule'] ?? '',
            ];
        }

        $serviceFilters = [
            'opportunity_uuid' => $filters['opportunity_uuid'] !== '' ? $filters['opportunity_uuid'] : null,
            'status' => $filters['status'] !== '' ? $filters['status'] : null,
            'type' => $filters['type'] !== '' ? $filters['type'] : null,
            'assigned_user_id' => $filters['assigned_user_id'],
            'date_from' => $filters['date_from'] !== '' ? $filters['date_from'] : null,
            'date_to' => $filters['date_to'] !== '' ? $filters['date_to'] : null,
            'is_schedule' => $filters['is_schedule'] !== '' ? $filters['is_schedule'] : null,
        ];

        $tasks = $this->taskService->list($serviceFilters);

        $outcomes = Outcome::query()
            ->orderBy('task_type')
            ->orderBy('name')
            ->get(['uuid', 'name', 'slug', 'task_type', 'is_refusal', 'opens_window']);

        $frontendFilters = [
            'opportunity_uuid' => $filters['opportunity_uuid'],
            'status' => $filters['status'],
            'type' => $filters['type'],
            'assigned_user_uuid' => $filters['assigned_user_uuid'],
            'date_from' => $filters['date_from'],
            'date_to' => $filters['date_to'],
            'is_schedule' => $filters['is_schedule'],
        ];

        return Inertia::render('tasks/Index', [
            'tasks' => TaskResource::collection($tasks),
            'filters' => $frontendFilters,
            'users' => $users,
            'outcomes' => $outcomes,
        ]);
    }

    public function setFilters(Request $request): array
    {
        $assignedUserUuid = $request->input('assigned_user_uuid', '');
        $assignedUserId = null;

        if ($assignedUserUuid !== '' && $assignedUserUuid !== null) {
            $assignedUserId = User::query()->where('uuid', $assignedUserUuid)->value('id');
        }

        $filters = [
            'opportunity_uuid' => $request->input('opportunity_uuid', ''),
            'status' => $request->input('status', ''),
            'type' => $request->input('type', ''),
            'assigned_user_uuid' => $assignedUserUuid,
            'assigned_user_id' => $assignedUserId,
            'date_from' => $request->input('date_from', ''),
            'date_to' => $request->input('date_to', ''),
            'is_schedule' => $request->input('is_schedule', ''),
        ];

        session(['task_filters' => $filters]);

        return $filters;
    }

    public function clearFilters(): RedirectResponse
    {
        session()->forget('task_filters');

        return to_route('tenant.tasks.index');
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        Gate::authorize('create', Task::class);

        $validated = $request->validated();

        $opportunity = Opportunity::query()->where('uuid', $validated['opportunity_uuid'])->firstOrFail();

        try {
            $this->taskService->create($opportunity, $validated);
        } catch (\DomainException $e) {
            if ($e->getMessage() === 'opportunity_has_open_task') {
                throw ValidationException::withMessages([
                    'opportunity_uuid' => [__('opportunity_has_open_task')],
                ]);
            }

            throw $e;
        }

        return to_route('tenant.tasks.index')
            ->with('success', 'Tarefa criada com sucesso.');
    }

    public function complete(CompleteTaskRequest $request, Task $task): JsonResponse
    {
        Gate::authorize('complete', $task);

        $validated = $request->validated();

        $outcome = Outcome::query()->where('uuid', $validated['outcome_uuid'])->firstOrFail();

        try {
            $result = $this->taskService->complete($task, $outcome, $validated);
        } catch (\DomainException $e) {
            if ($e->getMessage() === 'task_not_open') {
                throw ValidationException::withMessages([
                    'task' => [__('task_not_open')],
                ]);
            }

            if ($e->getMessage() === 'opportunity_status_terminal') {
                throw ValidationException::withMessages([
                    'task' => ['A oportunidade já se encontra em status terminal e não pode ser alterada.'],
                ]);
            }

            throw $e;
        }

        return response()->json([
            'message' => 'Tarefa concluída com sucesso.',
            'open_window' => $result['open_window'] ?? null,
        ]);
    }

    public function cancel(Request $request, Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $this->taskService->cancel($task);

        return to_route('tenant.tasks.index')
            ->with('success', 'Tarefa cancelada com sucesso.');
    }
}
