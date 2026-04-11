<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\AttachOpportunityRequest;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Models\Event;
use App\Models\EventType;
use App\Models\Grade;
use App\Models\Opportunity;
use App\Models\Room;
use App\Services\Event\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EventController extends Controller
{
    public function __construct(
        protected readonly EventService $eventService,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Event::class);

        $events = $this->eventService->list($request->all());

        return Inertia::render('events/Index', [
            'events' => $events,
            'filters' => $request->all(),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Event::class);

        $school = Auth::user()->currentSchool();

        return Inertia::render('events/Create', [
            'grades' => Grade::query()->orderBy('order')->get(['uuid', 'name', 'segment_id']),
            'rooms' => Room::query()->orderBy('name')->get(['uuid', 'name', 'capacity', 'is_external']),
            'school_name' => $school->trade_name ?? $school->legal_name,
            'event_types' => EventType::query()->where('is_active', true)->orderBy('name')->get(['uuid', 'name']),
        ]);
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        Gate::authorize('create', Event::class);

        $this->eventService->create($request->validated());

        return to_route('tenant.events.index')->with('success', 'Evento criado com sucesso.');
    }

    public function edit(Event $event): Response
    {
        Gate::authorize('update', $event);

        $school = Auth::user()->currentSchool();

        $event->load(['opportunities.student', 'rooms', 'grade', 'eventType']);

        return Inertia::render('events/Edit', [
            'event' => $event,
            'grades' => Grade::query()->orderBy('order')->get(['uuid', 'name', 'segment_id']),
            'rooms' => Room::query()->orderBy('name')->get(['uuid', 'name', 'capacity', 'is_external']),
            'school_name' => $school->trade_name ?? $school->legal_name,
            'event_types' => EventType::query()
                ->where(function ($q) use ($event): void {
                    $q->where('is_active', true)
                        ->orWhere('id', $event->event_type_id);
                })
                ->orderBy('name')
                ->get(['uuid', 'name', 'is_active']),
        ]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        Gate::authorize('update', $event);

        $this->eventService->update($event, $request->validated());

        return to_route('tenant.events.index')->with('success', 'Evento atualizado com sucesso.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        Gate::authorize('delete', $event);

        $this->eventService->delete($event);

        return to_route('tenant.events.index')->with('success', 'Evento excluído com sucesso.');
    }

    public function available(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Event::class);

        $events = $this->eventService->listAvailable();

        return response()->json($events);
    }

    public function availableOpportunities(Event $event): JsonResponse
    {
        Gate::authorize('update', $event);

        $opportunities = $this->eventService->listUnlinkedOpportunities($event);

        return response()->json(
            $opportunities->map(fn (Opportunity $opp) => [
                'uuid' => $opp->uuid,
                'created_at' => $opp->created_at,
                'guardian_name' => $opp->guardian?->name,
                'student_name' => $opp->student?->name,
                'status' => $opp->status?->value,
                'school_year_name' => $opp->schoolYear?->name,
                'registration_type' => $opp->registration_type,
            ])
        );
    }

    public function attachOpportunity(AttachOpportunityRequest $request, Event $event): RedirectResponse|JsonResponse
    {
        Gate::authorize('update', $event);

        $opportunity = Opportunity::where('uuid', $request->validated('opportunity_uuid'))->firstOrFail();

        try {
            $task = $this->eventService->attachOpportunity($event, $opportunity);
        } catch (\DomainException $e) {
            if ($e->getMessage() === 'opportunity_has_open_task') {
                throw ValidationException::withMessages([
                    'opportunity_uuid' => [__('opportunity_has_open_task')],
                ]);
            }

            throw $e;
        }

        if ($request->expectsJson()) {
            return response()->json(['task_uuid' => $task->uuid ?? null]);
        }

        return to_route('tenant.events.edit', ['event' => $event->uuid])->with('success', 'Oportunidade vinculada com sucesso.');
    }

    public function detachOpportunity(Event $event, Opportunity $opportunity): RedirectResponse
    {
        Gate::authorize('update', $event);

        $this->eventService->detachOpportunity($event, $opportunity);

        return to_route('tenant.events.edit', ['event' => $event->uuid])->with('success', 'Oportunidade desvinculada com sucesso.');
    }
}
