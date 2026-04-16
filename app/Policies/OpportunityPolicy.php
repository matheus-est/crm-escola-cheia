<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Opportunity;
use App\Models\User;

class OpportunityPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor', 'Comercial'], strict: true);
    }

    public function view(User $user, Opportunity $opportunity): bool
    {
        if (! in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor', 'Comercial'], strict: true)) {
            return false;
        }

        if ($user->isComercial()) {
            return $opportunity->responsible_user_id === $user->id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor', 'Comercial'], strict: true);
    }

    public function update(User $user, Opportunity $opportunity): bool
    {
        if (! in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor', 'Comercial'], strict: true)) {
            return false;
        }

        if ($user->isComercial()) {
            return $opportunity->responsible_user_id === $user->id;
        }

        return true;
    }

    public function delete(User $user, Opportunity $opportunity): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao'], strict: true);
    }
}
