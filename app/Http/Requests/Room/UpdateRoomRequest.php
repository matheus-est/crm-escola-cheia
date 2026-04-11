<?php

declare(strict_types=1);

namespace App\Http\Requests\Room;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_external' => filter_var($this->input('is_external'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'is_external' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'is_external' => 'Sala Externa',
        ];
    }
}
