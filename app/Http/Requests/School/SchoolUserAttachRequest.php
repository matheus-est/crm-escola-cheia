<?php

declare(strict_types=1);

namespace App\Http\Requests\School;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SchoolUserAttachRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'uuid', 'exists:users,uuid'],
            'role_id' => ['required', 'uuid', 'exists:roles,uuid'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->has('role_id')) {
                return;
            }

            $role = Role::query()->where('uuid', $this->input('role_id'))->first();

            if ($role !== null && in_array($role->name, ['Master', 'Admin', 'Operacao'], strict: true)) {
                $validator->errors()->add('role_id', 'Role inválida para vínculo de escola');
            }
        });
    }
}
