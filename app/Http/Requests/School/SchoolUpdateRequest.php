<?php

declare(strict_types=1);

namespace App\Http\Requests\School;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SchoolUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cnpj' => ['required', 'string', Rule::unique('schools', 'cnpj')->ignore($this->school->id, 'id')],
            'razao_social' => 'required|string|max:255',
            'logo_path' => 'nullable|string',
            'address_json' => 'nullable|array',
            'status' => 'nullable|string|in:active,inactive',
            'observations' => 'nullable|string',
            'unassigned_lead_alert_days' => 'nullable|integer|min:1',
            'units' => 'nullable|array',
            'units.*.nome' => 'required_with:units|string|max:255',
            'units.*.cep' => 'nullable|string',
            'units.*.logradouro' => 'nullable|string',
            'units.*.numero' => 'nullable|string',
            'units.*.complemento' => 'nullable|string',
            'units.*.bairro' => 'nullable|string',
            'units.*.cidade' => 'nullable|string',
            'units.*.estado' => 'nullable|string|max:2',
        ];
    }
}
