<?php

declare(strict_types=1);

namespace App\Http\Requests\Opportunity;

use App\Rules\CpfRule;
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
            'student_name' => ['required', 'string', 'max:255'],
            'student_cpf' => ['nullable', 'string', 'size:14', new CpfRule],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_cpf' => ['nullable', 'string', 'size:14', new CpfRule],
            'guardian_phone' => ['nullable', 'string', 'max:20'],
            'guardian_email' => ['nullable', 'email', 'max:255'],
            'zip_code' => ['nullable', 'string', 'max:9'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
            'neighborhood' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'size:2'],
        ];
    }
}
