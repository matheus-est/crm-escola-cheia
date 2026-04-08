<?php

declare(strict_types=1);

namespace App\Services\Opportunity;

use App\Enums\SchoolYearStatus;
use App\Models\Opportunity;
use App\Models\SchoolYear;
use App\Services\Guardian\GuardianService;
use App\Services\Student\StudentService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class OpportunityService
{
    public function __construct(
        protected readonly GuardianService $guardianService,
        protected readonly StudentService $studentService, 
    )
    {

    }

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Opportunity::query();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtros que chegam como UUID — resolve para ID antes de filtrar
        if (!empty($filters['grade_id'])) {
            $gradeId = Grade::where('uuid', $filters['grade_id'])->value('id');
            if ($gradeId) {
                $query->where('grade_id', $gradeId);
            }
        }

        if (!empty($filters['school_year_id'])) {
            $schoolYearId = SchoolYear::where('uuid', $filters['school_year_id'])->value('id');
            if ($schoolYearId) {
                $query->where('school_year_id', $schoolYearId);
            }
        }

        if (!empty($filters['responsible_user_id'])) {
            $userId = \App\Models\User::where('uuid', $filters['responsible_user_id'])->value('id');
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
        $guardian = $this->guardianService->findOrCreate([
            'nome'  => $data['guardian_name'],
            'cpf'   => $data['guardian_cpf'] ?? null,
            'phone' => $data['guardian_phone'] ?? null,
            'email' => $data['guardian_email'] ?? null,
        ]);

        $student = $this->studentService->findOrCreate([
            'nome'       => $data['student_name'],
            'cpf'        => $data['student_cpf'] ?? null,
        ]);

        $student->guardians()->syncWithoutDetaching([$guardian->id]);

        return Opportunity::create([
            'student_id'          => $student->id,
            'guardian_id'         => $guardian->id,
            'grade_id'            => $data['grade_id'],
            'school_year_id'      => $data['school_year_id'],
            'lead_source_id'      => $data['lead_source_id'] ?? null,
            'responsible_user_id' => $data['responsible_user_id'] ?? null,
            'registration_type'   => $data['registration_type'] ?? null,
            'history'             => $data['history'] ?? null,
            'indications'         => $data['indications'] ?? null,
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
