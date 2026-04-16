<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor', 'Comercial'], strict: true);
    }

    public function view(User $user, Task $task): bool
    {
        if ($task->school_id !== app('tenant.school_id')) {
            return false;
        }

        if ($user->isComercial()) {
            return $task->assigned_user_id === $user->id;
        }

        return true;
    }

    public function create(User $user): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Operacao', 'Gestor', 'Comercial'], strict: true);
    }

    public function update(User $user, Task $task): bool
    {
        if (in_array($user->role?->name, ['Master', 'Admin', 'Gestor', 'Operacao'], strict: true)) {
            return true;
        }

        if ($user->role?->name === 'Comercial') {
            return $task->assigned_user_id === $user->id;
        }

        return false;
    }

    public function complete(User $user, Task $task): bool
    {
        if (in_array($user->role?->name, ['Master', 'Admin', 'Gestor', 'Operacao'], strict: true)) {
            return $task->school_id === app('tenant.school_id');
        }

        if ($user->role?->name === 'Comercial') {
            return $task->assigned_user_id === $user->id;
        }

        return false;
    }

    public function delete(User $user, Task $task): bool
    {
        return in_array($user->role?->name, ['Master', 'Admin', 'Gestor'], strict: true);
    }
}
