<?php

declare(strict_types=1);

namespace App\Services\Event;

use App\Enums\TaskType;
use App\Models\Event;
use App\Models\Grade;
use App\Models\Opportunity;
use App\Models\Room;
use App\Models\Task;
use App\Services\Task\TaskService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventService
{
    public function __construct(
        private readonly TaskService $taskService,
    ) {}

    public function list(array $filters): LengthAwarePaginator
    {
        $query = Event::query()->withCount('opportunities');

        if (array_key_exists('title', $filters) && $filters['title'] !== null && $filters['title'] !== '') {
            $query->where('title', 'LIKE', '%'.$filters['title'].'%');
        }

        return $query->orderBy('event_date')->paginate(20);
    }

    public function listAvailable(): Collection
    {
        return Event::query()
            ->where(function ($q): void {
                $q->whereNull('event_date')
                    ->orWhere('event_date', '>=', now());
            })
            ->where(function ($q): void {
                $q->whereNull('max_capacity')
                    ->orWhereColumn('max_capacity', '>', DB::raw(
                        '(SELECT COUNT(*) FROM event_opportunity WHERE event_opportunity.event_id = events.id)'
                    ));
            })
            ->orderBy('event_date')
            ->get();
    }

    public function create(array $data): Event
    {
        $roomUuids = $data['room_uuids'] ?? [];
        unset($data['room_uuids']);

        if (array_key_exists('grade_uuid', $data)) {
            $gradeUuid = $data['grade_uuid'];
            unset($data['grade_uuid']);
            $data['grade_id'] = $gradeUuid !== null
                ? Grade::where('uuid', $gradeUuid)->value('id')
                : null;
        }

        $event = Event::create($data);

        if (! empty($roomUuids)) {
            $roomIds = Room::whereIn('uuid', $roomUuids)->pluck('id');
            $event->rooms()->attach($roomIds);
        }

        return $event;
    }

    public function update(Event $event, array $data): void
    {
        $hasRoomUuids = array_key_exists('room_uuids', $data);
        $roomUuids = $data['room_uuids'] ?? [];
        unset($data['room_uuids']);

        if (array_key_exists('grade_uuid', $data)) {
            $gradeUuid = $data['grade_uuid'];
            unset($data['grade_uuid']);
            $data['grade_id'] = $gradeUuid !== null
                ? Grade::where('uuid', $gradeUuid)->value('id')
                : null;
        }

        $event->update($data);

        if ($hasRoomUuids) {
            $event->rooms()->detach();
            if (! empty($roomUuids)) {
                $roomIds = Room::whereIn('uuid', $roomUuids)->pluck('id');
                $event->rooms()->attach($roomIds);
            }
        }
    }

    public function delete(Event $event): void
    {
        $event->delete();
    }

    public function attachOpportunity(Event $event, Opportunity $opportunity): Task
    {
        if ($event->opportunities()->where('opportunity_id', $opportunity->id)->exists()) {
            throw ValidationException::withMessages([
                'opportunity_uuid' => ['Oportunidade já vinculada a este evento.'],
            ]);
        }

        return DB::transaction(function () use ($event, $opportunity): Task {
            $event->opportunities()->attach($opportunity->id);

            return $this->taskService->create($opportunity, [
                'type' => TaskType::Evento,
                'due_at' => $event->event_date,
            ]);
        });
    }

    public function detachOpportunity(Event $event, Opportunity $opportunity): void
    {
        $event->opportunities()->detach($opportunity->id);
    }
}
