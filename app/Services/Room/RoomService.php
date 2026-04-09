<?php

declare(strict_types=1);

namespace App\Services\Room;

use App\Models\Room;
use Illuminate\Pagination\LengthAwarePaginator;

class RoomService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Room::query();

        if (! empty($filters['name'])) {
            $query->where('name', 'like', '%'.$filters['name'].'%');
        }

        return $query->orderBy('name')->paginate($filters['per_page'] ?? 20);
    }

    public function create(array $data): Room
    {
        return Room::create($data);
    }

    public function update(Room $room, array $data): void
    {
        $room->update($data);
    }

    public function delete(Room $room): void
    {
        $room->delete();
    }
}
