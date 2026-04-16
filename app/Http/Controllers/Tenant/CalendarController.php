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

        $hasParams = $request->hasAny(['date_from', 'date_to', 'assigned_user_uuid']);

        if ($request->isMethod('post') || $hasParams) {
            $calendarFilters = [
                'date_from' => $request->input('date_from', ''),
                'date_to' => $request->input('date_to', ''),
                'assigned_user_uuid' => $request->input('assigned_user_uuid', ''),
            ];
            session(['calendar_filters' => $calendarFilters]);
        } else {
            $stored = session('calendar_filters', []);
            $calendarFilters = [
                'date_from' => $stored['date_from'] ?? '',
                'date_to' => $stored['date_to'] ?? '',
                'assigned_user_uuid' => $stored['assigned_user_uuid'] ?? '',
            ];
        }

        $dateFrom = $calendarFilters['date_from'] !== ''
            ? $calendarFilters['date_from']
            : Carbon::now()->startOfMonth()->toDateTimeString();

        $dateTo = $calendarFilters['date_to'] !== ''
            ? $calendarFilters['date_to']
            : Carbon::now()->endOfMonth()->toDateTimeString();

        $assignedUserId = $this->resolveAssignedUserId($calendarFilters['assigned_user_uuid']);

        $entries = $this->calendarService->listEntries($dateFrom, $dateTo, $assignedUserId);

        /** @var User $authUser */
        $authUser = Auth::user();

        $canFilterByUser = $authUser->isCrossTenant() || $authUser->role?->name === 'Gestor';
        $users = $canFilterByUser
            ? $authUser->currentSchool()->users()->get(['users.uuid', 'users.name'])
            : collect();

        return Inertia::render('calendar/Index', [
            'entries' => $entries,
            'filters' => $calendarFilters,
            'users' => $users,
            'currentUser' => $authUser->uuid,
        ]);
    }

    public function entries(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Task::class);

        $dateFrom = $request->string('date_from', Carbon::now()->startOfMonth()->toDateTimeString())->toString();
        $dateTo = $request->string('date_to', Carbon::now()->endOfMonth()->toDateTimeString())->toString();

        $uuid = $request->string('assigned_user_uuid')->toString();
        $assignedUserId = $this->resolveAssignedUserId($uuid);

        $entries = $this->calendarService->listEntries($dateFrom, $dateTo, $assignedUserId);

        return response()->json($entries);
    }

    private function resolveAssignedUserId(string $uuid): ?int
    {
        /** @var User $authUser */
        $authUser = Auth::user();

        if ($uuid === '') {
            if (! $authUser->isCrossTenant() && $authUser->role?->name !== 'Gestor') {
                return $authUser->id;
            }

            return null;
        }

        return User::query()->where('uuid', $uuid)->value('id');
    }
}
