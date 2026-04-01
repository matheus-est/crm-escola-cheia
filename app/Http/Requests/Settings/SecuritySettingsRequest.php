<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for validating security settings.
 */
class SecuritySettingsRequest extends FormRequest
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
            'session_timeout' => ['required', 'integer', 'min:5'],
            'password_expiration_days' => ['required', 'integer', 'min:0'],
            'allow_self_deletion' => ['required', 'boolean'],
        ];
    }
}
