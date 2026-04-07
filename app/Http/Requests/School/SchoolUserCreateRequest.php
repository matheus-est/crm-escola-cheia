<?php

declare(strict_types=1);

namespace App\Http\Requests\School;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolUserCreateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'role_id' => [
                'required',
                'string',
                Rule::exists('roles', 'uuid')->whereIn('name', ['Gestor', 'Comercial']),
            ],
        ];
    }
}
