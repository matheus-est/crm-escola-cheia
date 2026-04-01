<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\User;
use App\Services\Acl\UserService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && app(UserService::class)->hasPermission($user, 'users_edit');
    }

    public function rules(): array
    {
        $userUuid = $this->route('user_uuid');
        $currentUser = $this->user();
        $isCurrentUserMaster = $currentUser && $currentUser->role && $currentUser->role->name === 'Master';

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userUuid, 'uuid'),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['nullable', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
        ];

        if (! $isCurrentUserMaster) {
            $rules['role_id'][] = function ($attribute, $value, $fail) use ($userUuid) {
                $targetUser = User::where('uuid', $userUuid)->first();
                if ($targetUser && $targetUser->role && $targetUser->role->name === 'Master') {
                    $fail('Você não tem permissão para alterar a função deste usuário.');
                }

                $newRole = Role::find($value);
                if ($newRole && $newRole->name === 'Master') {
                    $fail('Você não tem permissão para atribuir a função Master.');
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
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'As senhas não conferem.',
            'role_id.required' => 'Selecione uma função.',
        ];
    }
}
