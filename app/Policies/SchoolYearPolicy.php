<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SchoolYear;
use App\Models\User;

class SchoolYearPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor'], strict: true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor'], strict: true);
    }

    public function update(User $user, SchoolYear $schoolYear): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor'], strict: true);
    }

    public function delete(User $user, SchoolYear $schoolYear): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor'], strict: true);
    }
}
