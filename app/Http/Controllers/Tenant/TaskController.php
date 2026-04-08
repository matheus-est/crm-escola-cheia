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
use App\Services\Task\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $tasks = $this->taskService->list($request->only([
            'opportunity_uuid',
            'status',
            'type',
            'assigned_user_id',
        ]));

        return Inertia::render('tasks/Index', [
            'tasks' => TaskResource::collection($tasks),
            'filters' => $request->only(['opportunity_uuid', 'status', 'type', 'assigned_user_id']),
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

            throw $e;
        }

        return response()->json([
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
