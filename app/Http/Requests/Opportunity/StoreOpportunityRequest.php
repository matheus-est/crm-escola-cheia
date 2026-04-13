<?php

declare(strict_types=1);

namespace App\Http\Requests\Opportunity;

use App\Enums\TaskType;
use App\Models\Grade;
use App\Models\LeadSource;
use App\Models\SchoolYear;
use App\Models\Segment;
use App\Models\User;
use App\Rules\CpfRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreOpportunityRequest extends FormRequest
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
            $resolved = Segment::query()->where('uuid', $this->input('segment_id'))->value('id');
            if ($resolved !== null) {
                $this->merge(['segment_id' => $resolved]);
            }
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
            'task_type' => ['nullable', Rule::enum(TaskType::class)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $taskType = $this->input('task_type');
            $registrationType = $this->input('registration_type');

            if ($taskType === null || $registrationType === null) {
                return;
            }

            $allowed = array_map(
                fn (TaskType $t) => $t->value,
                TaskType::forRegistrationType($registrationType)
            );

            if (! in_array($taskType, $allowed, true)) {
                $validator->errors()->add(
                    'task_type',
                    'O tipo de tarefa não é compatível com o tipo de cadastro selecionado.'
                );
            }
        });

        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            // Uniqueness: same student + same school year + same tenant
            $studentCpf = $this->input('student_cpf');
            $schoolYearId = $this->input('school_year_id'); // already integer after prepareForValidation
            $tenantSchoolId = app('tenant.school_id');

            if ($studentCpf !== null && $schoolYearId !== null && $tenantSchoolId !== null) {
                $studentId = DB::table('students')
                    ->where('cpf', $studentCpf)
                    ->value('id');

                if ($studentId !== null) {
                    $exists = DB::table('opportunities')
                        ->where('student_id', $studentId)
                        ->where('school_year_id', $schoolYearId)
                        ->where('school_id', $tenantSchoolId)
                        ->whereNull('deleted_at')
                        ->exists();

                    if ($exists) {
                        $validator->errors()->add(
                            'student_cpf',
                            'Este aluno já possui uma oportunidade cadastrada para este Ano Letivo.'
                        );
                    }
                }
            }
        });
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
            'task_type' => 'Tarefa Vinculada',
        ];
    }
}
