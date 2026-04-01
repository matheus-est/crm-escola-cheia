<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for validating LGPD settings.
 */
class LgpdsSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Authorization is handled in the controller via middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:200'],
            'dpo_email' => ['required', 'email', 'max:150'],
            'audit_retention_days' => ['required', 'integer', 'min:30'],
        ];
    }
}
