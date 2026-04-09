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

    protected function prepareForValidation(): void
    {
        if ($this->has('cnpj')) {
            $raw = preg_replace('/\D/', '', (string) $this->input('cnpj'));
            if (strlen($raw) === 14) {
                $masked = substr($raw, 0, 2).'.'.substr($raw, 2, 3).'.'.substr($raw, 5, 3)
                        .'/'.substr($raw, 8, 4).'-'.substr($raw, 12, 2);
                $this->merge(['cnpj' => $masked]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'cnpj' => ['required', 'string', Rule::unique('schools', 'cnpj')->ignore($this->school->id, 'id')],
            'legal_name' => 'required|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'logo_path' => 'nullable|string',
            'address_json' => 'nullable|array',
            'status' => 'nullable|string|in:active,inactive',
            'observations' => 'nullable|string',
            'unassigned_lead_alert_days' => 'nullable|integer|min:1',
            'units' => 'nullable|array',
            'units.*.name' => 'required_with:units|string|max:255',
            'units.*.zip_code' => 'nullable|string',
            'units.*.street' => 'nullable|string',
            'units.*.number' => 'nullable|string',
            'units.*.complement' => 'nullable|string',
            'units.*.neighborhood' => 'nullable|string',
            'units.*.city' => 'nullable|string',
            'units.*.state' => 'nullable|string|max:2',
        ];
    }
}
