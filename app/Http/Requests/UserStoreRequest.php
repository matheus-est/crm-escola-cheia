<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Role;
use App\Services\Acl\UserService;
use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && app(UserService::class)->hasPermission($user, 'users_add');
    }

    public function rules(): array
    {
        $currentUser = $this->user();
        $isCurrentUserMaster = $currentUser && $currentUser->role && $currentUser->role->name === 'Master';

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role_id' => ['required', 'exists:roles,id'],
        ];

        if (! $isCurrentUserMaster) {
            $rules['role_id'][] = function ($attribute, $value, $fail) {
                $role = Role::find($value);
                if ($role && $role->name === 'Master') {
                    $fail('Você não tem permissão para criar usuários com função Master.');
                }
            };
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O email é obrigatório.',
            'email.unique' => 'Este email já está em uso.',
            'role_id.required' => 'Selecione uma função.',
        ];
    }
}
