<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $schoolId = app('tenant.school_id') ?? $this->user()->school_current_id;
        $gradeClass = $this->route('grade');
        $gradeId = $gradeClass->id ?? null;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('grades')->where(function ($query) use ($schoolId) {
                    return $query->where('school_id', $schoolId);
                })->ignore($gradeId),
            ],
            'segment_uuid' => ['required', 'string', 'exists:segments,uuid'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
