<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\Acl\UserService;
use Illuminate\Foundation\Http\FormRequest;

class RoleStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && app(UserService::class)->hasPermission($user, 'roles_add');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['required', 'array'],
            'permissions.*' => ['exists:permissions,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da função é obrigatório.',
            'name.unique' => 'Este nome de função já está em uso.',
            'permissions.required' => 'Selecione pelo menos uma permissão.',
        ];
    }
}
