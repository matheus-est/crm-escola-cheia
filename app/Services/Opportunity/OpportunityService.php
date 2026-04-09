<?php

declare(strict_types=1);

namespace App\Services\Opportunity;

use App\Enums\SchoolYearStatus;
use App\Models\Grade;
use App\Models\Opportunity;
use App\Models\SchoolYear;
use App\Models\User;
use App\Services\Guardian\GuardianService;
use App\Services\Student\StudentService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class OpportunityService
{
    public function __construct(
        protected readonly GuardianService $guardianService,
        protected readonly StudentService $studentService,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Opportunity::query();

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['grade_id'])) {
            $gradeId = Grade::where('uuid', $filters['grade_id'])->value('id');
            if ($gradeId) {
                $query->where('grade_id', $gradeId);
            }
        }

        if (! empty($filters['school_year_id'])) {
            $schoolYearId = SchoolYear::where('uuid', $filters['school_year_id'])->value('id');
            if ($schoolYearId) {
                $query->where('school_year_id', $schoolYearId);
            }
        }

        if (! empty($filters['responsible_user_id'])) {
            $userId = User::where('uuid', $filters['responsible_user_id'])->value('id');
            if ($userId) {
                $query->where('responsible_user_id', $userId);
            }
        }

        $perPage = isset($filters['per_page']) ? (int) $filters['per_page'] : 15;

        return $query
            ->with(['student', 'guardian', 'grade', 'schoolYear', 'responsibleUser'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function create(array $data): Opportunity
    {
        $guardianId = null;

        if (! empty($data['guardian_name'])) {
            $guardianData = array_filter([
                'nome' => $data['guardian_name'],
                'cpf' => $data['guardian_cpf'] ?? null,
                'telefone' => $data['guardian_phone'] ?? null,
                'email' => $data['guardian_email'] ?? null,
                'cep' => $data['zip_code'] ?? null,
                'logradouro' => $data['street'] ?? null,
                'numero' => $data['number'] ?? null,
                'bairro' => $data['neighborhood'] ?? null,
                'cidade' => $data['city'] ?? null,
                'estado' => $data['state'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');

            $guardian = $this->guardianService->findOrCreate($guardianData);
            $guardianId = $guardian->id;
        }

        $student = $this->studentService->findOrCreate(array_filter([
            'nome' => $data['student_name'],
            'cpf' => $data['student_cpf'] ?? null,
        ], fn ($v) => $v !== null && $v !== ''));

        if ($guardianId !== null) {
            $student->guardians()->syncWithoutDetaching([$guardianId]);
        }

        return Opportunity::create([
            'student_id' => $student->id,
            'guardian_id' => $guardianId,
            'grade_id' => $data['grade_id'],
            'school_year_id' => $data['school_year_id'],
            'lead_source_id' => $data['lead_source_id'] ?? null,
            'responsible_user_id' => $data['responsible_user_id'] ?? null,
            'registration_type' => $data['registration_type'] ?? null,
            'segment_id' => $data['segment_id'] ?? null,
            'history' => $data['history'] ?? null,
            'indications' => $data['indications'] ?? null,
        ]);
    }

    public function update(Opportunity $opportunity, array $data): Opportunity
    {
        if ($opportunity->status->isTerminal()) {
            throw ValidationException::withMessages([
                'status' => ['Oportunidade em status terminal não pode ser editada.'],
            ]);
        }

        $opportunity->fill($data);
        $opportunity->save();

        return $opportunity;
    }

    public function hasClosedSchoolYear(array $data): bool
    {
        if (! array_key_exists('school_year_id', $data) || $data['school_year_id'] === null) {
            return false;
        }

        $schoolYear = SchoolYear::find($data['school_year_id']);

        return $schoolYear !== null && $schoolYear->status === SchoolYearStatus::Encerrado;
    }
}
