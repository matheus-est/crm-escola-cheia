<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\LeadSource;
use App\Models\User;

class LeadSourcePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor'], strict: true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor'], strict: true);
    }

    public function update(User $user, LeadSource $leadSource): bool
    {
        if ($leadSource->is_system) {
            return false;
        }

        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor'], strict: true);
    }

    public function delete(User $user, LeadSource $leadSource): bool
    {
        if ($leadSource->is_system) {
            return false;
        }

        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor'], strict: true);
    }
}
