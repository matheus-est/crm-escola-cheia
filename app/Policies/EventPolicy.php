<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor', 'Comercial'], strict: true);
    }

    public function view(User $user, Event $event): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor', 'Comercial'], strict: true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor', 'Comercial'], strict: true);
    }

    public function update(User $user, Event $event): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor', 'Comercial'], strict: true);
    }

    public function delete(User $user, Event $event): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao'], strict: true);
    }
}
