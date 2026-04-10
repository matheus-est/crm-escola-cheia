<?php

declare(strict_types=1);

namespace App\Services\EventType;

use App\Models\EventType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EventTypeService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = EventType::query();

        if (array_key_exists('name', $filters) && $filters['name'] !== null && $filters['name'] !== '') {
            $query->where('name', 'LIKE', '%'.$filters['name'].'%');
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '') {
            $query->where('is_active', (bool) $filters['is_active']);
        }

        return $query->orderBy('name')->paginate($filters['per_page'] ?? 20);
    }

    public function create(array $data): EventType
    {
        return EventType::create(array_merge($data, [
            'school_id' => app('tenant.school_id'),
            'is_system' => false,
        ]));
    }

    public function update(EventType $eventType, array $data): void
    {
        $eventType->update($data);
    }

    public function toggleActive(EventType $eventType): void
    {
        if ($eventType->is_system === true) {
            throw new \DomainException('Tipos de evento padrão do sistema não podem ser modificados.');
        }

        $eventType->update(['is_active' => ! $eventType->is_active]);
    }

    public function deactivate(EventType $eventType): void
    {
        if ($eventType->is_system === true) {
            throw new \DomainException('Tipos de evento padrão do sistema não podem ser desativados.');
        }

        $eventType->update(['is_active' => false]);
    }
}
