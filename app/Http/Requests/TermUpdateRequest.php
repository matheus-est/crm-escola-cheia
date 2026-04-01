<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TermUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $termUuid = $this->route('term_uuid');

        return [
            'title'        => ['required', 'string', 'max:255'],
            'version'      => ['required', 'string', 'max:20', "unique:term_versions,version,{$termUuid},uuid"],
            'content'      => ['required', 'string'],
            'effective_at' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'        => 'O título é obrigatório.',
            'version.required'      => 'A versão é obrigatória.',
            'version.unique'        => 'Já existe um termo com esta versão.',
            'content.required'      => 'O conteúdo é obrigatório.',
            'effective_at.required' => 'A data de vigência é obrigatória.',
            'effective_at.date'     => 'A data de vigência deve ser uma data válida.',
        ];
    }
}