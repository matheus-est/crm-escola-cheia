<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Grade;
use App\Models\User;

class GradePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor', 'Comercial'], strict: true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor'], strict: true);
    }

    public function update(User $user, Grade $grade): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor'], strict: true);
    }

    public function delete(User $user, Grade $grade): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor'], strict: true);
    }
}
