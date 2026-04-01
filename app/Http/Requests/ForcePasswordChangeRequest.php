<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ForcePasswordChangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'regex:/[!@#$%^&*()\-_=+\[\]{}|;:,.<>?]/',
            ],
            'accepted_terms' => ['required', 'accepted'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required'  => 'A senha é obrigatória.',
            'password.min'       => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'As senhas não coincidem.',
            'password.regex'     => 'A senha deve conter letras maiúsculas, minúsculas, números e caracteres especiais.',
            'accepted_terms.required' => 'Você precisa aceitar os Termos de Uso.',
            'accepted_terms.accepted' => 'Você precisa aceitar os Termos de Uso.',
        ];
    }
}