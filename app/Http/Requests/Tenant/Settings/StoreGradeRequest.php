<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $schoolId = app('tenant.school_id') ?? $this->user()->school_current_id;

        return [
            'nome' => [
                'required',
                'string',
                'max:255',
                Rule::unique('grades')->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId);
                }),
            ],
            'segment_uuid' => ['required', 'string', 'exists:segments,uuid'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
