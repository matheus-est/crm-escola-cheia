<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Room\StoreRoomRequest;
use App\Http\Requests\Room\UpdateRoomRequest;
use App\Models\Room;
use App\Services\Room\RoomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RoomController extends Controller
{
    public function __construct(
        protected readonly RoomService $roomService,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Room::class);

        $filters = [
            'name' => $request->input('name', ''),
            'per_page' => $request->input('per_page', 10),
        ];

        $rooms = $this->roomService->list($filters);

        return Inertia::render('tenant-settings/Rooms', [
            'rooms' => $rooms,
            'filters' => $filters,
        ]);
    }

    public function store(StoreRoomRequest $request): RedirectResponse
    {
        Gate::authorize('create', Room::class);

        $this->roomService->create($request->validated());

        return back()->with('success', 'Sala criada com sucesso.');
    }

    public function update(UpdateRoomRequest $request, Room $room): RedirectResponse
    {
        Gate::authorize('update', $room);

        $this->roomService->update($room, $request->validated());

        return back()->with('success', 'Sala atualizada com sucesso.');
    }

    public function destroy(Request $request, Room $room): RedirectResponse
    {
        if (! Hash::check($request->string('password')->value(), auth()->user()->password)) {
            throw ValidationException::withMessages([
                'password' => ['Senha incorreta.'],
            ]);
        }

        Gate::authorize('delete', $room);

        $this->roomService->delete($room);

        return back()->with('success', 'Sala excluída com sucesso.');
    }
}
