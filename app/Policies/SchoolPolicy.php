<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\School;
use App\Models\User;

class SchoolPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao'], strict: true);
    }

    public function view(User $user, School $school): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao'], strict: true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao'], strict: true);
    }

    public function update(User $user, School $school): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin'], strict: true);
    }

    public function delete(User $user, School $school): bool
    {
        return $user->role?->name === 'Master';
    }
}
