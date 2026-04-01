<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for validating identity settings.
 */
class IdentitySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:100'],
            'logo_light' => ['nullable', 'file', 'mimes:jpeg,png,webp,svg', 'max:2048'],
            'logo_dark' => ['nullable', 'file', 'mimes:jpeg,png,webp,svg', 'max:2048'],
            'favicon' => ['nullable', 'file', 'image', 'mimes:ico,jpeg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'app_name.required' => 'O nome do sistema é obrigatório.',
            'logo_light.image' => 'O arquivo deve ser uma imagem.',
            'logo_light.mimes' => 'Formatos aceitos: JPEG, PNG, WebP, SVG.',
            'logo_light.max' => 'A imagem não pode ultrapassar 2MB.',
            'logo_dark.image' => 'O arquivo deve ser uma imagem.',
            'logo_dark.mimes' => 'Formatos aceitos: JPEG, PNG, WebP, SVG.',
            'logo_dark.max' => 'A imagem não pode ultrapassar 2MB.',
            'favicon.image' => 'O arquivo deve ser uma imagem.',
            'favicon.mimes' => 'Formatos aceitos: ICO, JPEG, PNG.',
            'favicon.max' => 'O favicon não pode ultrapassar 2MB.',
        ];
    }
}
