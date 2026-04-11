<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use App\Services\Calendar\CalendarService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class CalendarController extends Controller
{
    public function __construct(private readonly CalendarService $calendarService) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Task::class);

        $dateFrom = $request->string('date_from', Carbon::now()->startOfMonth()->toDateTimeString())->toString();
        $dateTo   = $request->string('date_to', Carbon::now()->endOfMonth()->toDateTimeString())->toString();

        $assignedUserId = $this->resolveAssignedUserId($request);

        $entries = $this->calendarService->listEntries($dateFrom, $dateTo, $assignedUserId);

        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        $canFilterByUser = $authUser->isCrossTenant() || $authUser->role?->name === 'Gestor';
        $users = $canFilterByUser
            ? $authUser->currentSchool()->users()->get(['users.uuid', 'users.name'])
            : collect();

        return Inertia::render('calendar/Index', [
            'entries'     => $entries,
            'filters'     => $request->only(['date_from', 'date_to', 'assigned_user_uuid']),
            'users'       => $users,
            'currentUser' => $authUser->uuid,
        ]);
    }

    public function entries(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Task::class);

        $dateFrom = $request->string('date_from', Carbon::now()->startOfMonth()->toDateTimeString())->toString();
        $dateTo   = $request->string('date_to', Carbon::now()->endOfMonth()->toDateTimeString())->toString();

        $assignedUserId = $this->resolveAssignedUserId($request);

        $entries = $this->calendarService->listEntries($dateFrom, $dateTo, $assignedUserId);

        return response()->json($entries);
    }

    private function resolveAssignedUserId(Request $request): ?int
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        $uuid = $request->string('assigned_user_uuid')->toString();
        if ($uuid === '') {
            if (! $authUser->isCrossTenant() && $authUser->role?->name !== 'Gestor') {
                return $authUser->id;
            }

            return null;
        }

        return User::query()->where('uuid', $uuid)->value('id');
    }
}
