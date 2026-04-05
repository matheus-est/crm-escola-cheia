<?php

declare(strict_types=1);

namespace App\Http\Requests\Opportunity;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['nullable', 'exists:students,id'],
            'guardian_id' => ['nullable', 'exists:guardians,id'],
            'grade_id' => ['nullable', 'exists:grades,id'],
            'school_year_id' => ['nullable', 'exists:school_years,id'],
            'lead_source_id' => ['nullable', 'exists:lead_sources,id'],
            'responsible_user_id' => ['nullable', 'exists:users,id'],
            'observations' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
