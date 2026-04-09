<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Room;
use App\Models\User;

class RoomPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor', 'Comercial'], strict: true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor'], strict: true);
    }

    public function update(User $user, Room $room): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor'], strict: true);
    }

    public function delete(User $user, Room $room): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor'], strict: true);
    }
}
