<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for validating email settings.
 */
class EmailSettingsRequest extends FormRequest
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
            'mail_from_name' => ['required', 'string', 'max:100'],
            'mail_from_address' => ['required', 'email', 'max:150'],
        ];
    }
}
