<?php

declare(strict_types=1);

namespace App\Http\Requests\EventType;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEventTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('event_types', 'name')
                    ->where('school_id', app('tenant.school_id'))
                    ->ignore($this->eventType->id),
            ],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
