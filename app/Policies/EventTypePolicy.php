<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EventType;
use App\Models\User;

class EventTypePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor'], strict: true);
    }

    public function view(User $user, EventType $eventType): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor'], strict: true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor'], strict: true);
    }

    public function update(User $user, EventType $eventType): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor'], strict: true);
    }

    public function delete(User $user, EventType $eventType): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor'], strict: true);
    }
}
