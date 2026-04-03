<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class StudentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:150'],
            'cpf' => ['nullable', 'string', 'regex:/^\d{3}\.\d{3}\.\d{3}-\d{2}$/'],
            'data_nascimento' => ['nullable', 'date'],
        ];
    }
}
