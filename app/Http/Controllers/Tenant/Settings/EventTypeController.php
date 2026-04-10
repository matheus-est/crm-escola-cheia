<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\EventType\StoreEventTypeRequest;
use App\Http\Requests\EventType\UpdateEventTypeRequest;
use App\Http\Resources\EventTypeResource;
use App\Models\EventType;
use App\Services\EventType\EventTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class EventTypeController extends Controller
{
    public function __construct(
        protected readonly EventTypeService $eventTypeService,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', EventType::class);

        $filters = [
            'name' => $request->input('name', ''),
            'is_active' => $request->input('is_active', ''),
            'per_page' => $request->input('per_page', 20),
        ];

        $eventTypes = $this->eventTypeService->list($filters);

        return Inertia::render('tenant-settings/EventTypes', [
            'event_types' => EventTypeResource::collection($eventTypes),
            'filters' => $filters,
        ]);
    }

    public function store(StoreEventTypeRequest $request): RedirectResponse
    {
        Gate::authorize('create', EventType::class);

        $this->eventTypeService->create($request->validated());

        return back()->with('success', 'Tipo de evento criado com sucesso.');
    }

    public function update(UpdateEventTypeRequest $request, EventType $eventType): RedirectResponse
    {
        Gate::authorize('update', $eventType);

        $this->eventTypeService->update($eventType, $request->validated());

        return back()->with('success', 'Tipo de evento atualizado com sucesso.');
    }

    public function destroy(EventType $eventType): RedirectResponse
    {
        Gate::authorize('delete', $eventType);

        if ($eventType->is_system === true) {
            return back()->withErrors(['eventType' => 'Tipos de evento padrão não podem ser modificados.']);
        }

        $this->eventTypeService->deactivate($eventType);

        return back()->with('success', 'Tipo de evento inativado com sucesso.');
    }

    public function toggleActive(EventType $eventType): RedirectResponse
    {
        Gate::authorize('delete', $eventType);

        if ($eventType->is_system === true) {
            return back()->withErrors(['eventType' => 'Tipos de evento padrão não podem ser modificados.']);
        }

        $this->eventTypeService->toggleActive($eventType);

        return back()->with('success', 'Tipo de evento reativado com sucesso.');
    }
}
