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

        $assignedUserId = null;
        if ($request->filled('assigned_user_uuid')) {
            $user = User::query()->where('uuid', $request->input('assigned_user_uuid'))->first();
            $assignedUserId = $user?->id;
        }

        $filters = [
            'opportunity_uuid' => $request->input('opportunity_uuid'),
            'status' => $request->input('status'),
            'type' => $request->input('type'),
            'assigned_user_id' => $assignedUserId,
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'is_schedule' => $request->input('is_schedule'),
        ];

        $tasks = $this->taskService->list($filters);

        $outcomes = Outcome::query()
            ->orderBy('task_type')
            ->orderBy('name')
            ->get(['uuid', 'name', 'slug', 'task_type', 'is_refusal', 'opens_window']);

        return Inertia::render('tasks/Index', [
            'tasks' => TaskResource::collection($tasks),
            'filters' => $request->only(['opportunity_uuid', 'status', 'type', 'assigned_user_uuid', 'date_from', 'date_to', 'is_schedule']),
            'users' => $users,
            'outcomes' => $outcomes,
        ]);
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
