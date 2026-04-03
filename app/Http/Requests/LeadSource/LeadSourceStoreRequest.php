<?php

declare(strict_types=1);

namespace App\Http\Requests\LeadSource;

use Illuminate\Foundation\Http\FormRequest;

class LeadSourceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }
}
