<?php

declare(strict_types=1);

namespace App\Services\School;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\Acl\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SchoolUserCreationService
{
    public function __construct(
        protected readonly UserService $userService)
    {
        //
    }

    public function createAndAttach(School $school, array $data): User
    {
        $existingUser = User::query()->where('email', $data['email'])->first();

        if ($existingUser !== null) {
            if ($school->users()->where('users.id', $existingUser->id)->exists()) {
                throw ValidationException::withMessages([
                    'responsaveis' => 'Usuário já vinculado a esta escola.',
                ]);
            }

            $school->users()->attach($existingUser->id, ['is_active' => true]);

            throw ValidationException::withMessages([
                'responsaveis' => "E-mail ({$existingUser->email}) já cadastrado no sistema. Vinculado à escola com sucesso.",
            ]);
        }

        $role = Role::query()->where('uuid', $data['role_id'])->firstOrFail();

        if (! in_array($role->name, ['Gestor', 'Comercial'], strict: true)) {
            throw ValidationException::withMessages([
                'responsaveis' => 'Perfil inválido para responsável de escola.',
            ]);
        }

        return DB::transaction(function () use ($school, $role, $data): User {
            $user = $this->userService->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'role_id' => $role->id,
            ]);

            $school->users()->attach($user->id, ['is_active' => true]);

            return $user;
        });
    }
}
