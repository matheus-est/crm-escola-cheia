<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Services\Acl\UserService;
use Illuminate\Foundation\Http\FormRequest;

class PermissionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && app(UserService::class)->hasPermission($user, 'permissions_add');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:permissions,name', 'regex:/^[a-z_]+$/'],
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da permissão é obrigatório.',
            'name.unique' => 'Esta permissão já existe.',
            'name.regex' => 'O nome deve conter apenas letras minúsculas e underscore.',
            'module_id.required' => 'O módulo é obrigatório.',
            'module_id.exists' => 'Módulo não encontrado.',
        ];
    }
}
