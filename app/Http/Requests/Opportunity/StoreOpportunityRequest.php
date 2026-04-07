<?php

declare(strict_types=1);

namespace App\Http\Requests\Opportunity;

use Illuminate\Foundation\Http\FormRequest;

class StoreOpportunityRequest extends FormRequest
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
            'grade_id' => ['required', 'exists:grades,id'],
            'school_year_id' => ['required', 'exists:school_years,id'],
            'lead_source_id' => ['nullable', 'exists:lead_sources,id'],
            'responsible_user_id' => ['nullable', 'exists:users,id'],
            'observations' => ['nullable', 'string', 'max:2000'],
            'history' => ['nullable', 'string', 'max:5000'],
            'indications' => ['nullable', 'string', 'max:2000'],
            'registration_type' => ['nullable', 'string', 'in:agendamento,evento'],
            'segment_id' => ['nullable', 'exists:segments,id'],
        ];
    }
}
