<?php

declare(strict_types=1);

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $hasNoDate = filter_var($this->input('has_no_date'), FILTER_VALIDATE_BOOLEAN);

        if ($hasNoDate) {
            $this->merge(['event_date' => null, 'has_no_date' => true]);
        } else {
            $this->merge(['has_no_date' => false]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'event_type' => ['nullable', 'string', 'max:60'],
            'has_no_date' => ['sometimes', 'boolean'],
            'grade_uuid' => ['nullable', 'exists:grades,uuid'],
            'event_date' => ['required_unless:has_no_date,true', 'nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'max_capacity' => ['nullable', 'integer', 'min:1'],
            'room_uuids' => ['nullable', 'array'],
            'room_uuids.*' => ['exists:rooms,uuid'],
        ];
    }
}
