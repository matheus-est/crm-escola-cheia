<?php

declare(strict_types=1);

namespace App\Http\Requests\Opportunity;

use App\Models\Grade;
use App\Models\LeadSource;
use App\Models\SchoolYear;
use App\Models\Segment;
use App\Models\User;
use App\Rules\CpfRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOpportunityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('grade_id')) {
            $this->merge([
                'grade_id' => Grade::query()->where('uuid', $this->input('grade_id'))->value('id'),
            ]);
        }

        if ($this->filled('segment_id')) {
            $this->merge([
                'segment_id' => Segment::query()->where('uuid', $this->input('segment_id'))->value('id'),
            ]);
        }

        if ($this->filled('school_year_id')) {
            $this->merge([
                'school_year_id' => SchoolYear::query()->where('uuid', $this->input('school_year_id'))->value('id'),
            ]);
        }

        if ($this->filled('lead_source_id')) {
            $this->merge([
                'lead_source_id' => LeadSource::query()->where('uuid', $this->input('lead_source_id'))->value('id'),
            ]);
        }

        if ($this->filled('responsible_user_id')) {
            $this->merge([
                'responsible_user_id' => User::query()->where('uuid', $this->input('responsible_user_id'))->value('id'),
            ]);
        }
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
            'history' => ['nullable', 'string', 'max:5000'],
            'indications' => ['nullable', 'string', 'max:2000'],
            'registration_type' => ['nullable', 'string', 'in:agendamento,evento'],
            'segment_id' => ['nullable', 'exists:segments,id'],
            'student_name' => ['nullable', 'string', 'max:255'],
            'student_cpf' => ['nullable', 'string', 'size:14', new CpfRule],
            'student_birth_date' => ['nullable', 'date'],
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

    public function attributes(): array
    {
        return [
            'grade_id' => 'Série/Turma',
            'segment_id' => 'Segmento',
            'school_year_id' => 'Ano Letivo',
            'lead_source_id' => 'Origem do Lead',
            'responsible_user_id' => 'Responsável pelo Atendimento',
            'student_name' => 'Nome do Aluno',
            'student_cpf' => 'CPF do Aluno',
            'student_birth_date' => 'Data de Nascimento',
            'guardian_name' => 'Nome do Responsável',
            'guardian_cpf' => 'CPF do Responsável',
            'registration_type' => 'Tipo de Cadastro',
        ];
    }
}
