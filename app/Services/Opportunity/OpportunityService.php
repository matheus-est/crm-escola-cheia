<?php

declare(strict_types=1);

namespace App\Services\Opportunity;

use App\Enums\OpportunityStatus;
use App\Enums\SchoolYearStatus;
use App\Enums\TaskType;
use App\Models\Grade;
use App\Models\LeadSource;
use App\Models\Opportunity;
use App\Models\SchoolUnit;
use App\Models\SchoolYear;
use App\Models\Segment;
use App\Models\User;
use App\Services\Guardian\GuardianService;
use App\Services\Student\StudentService;
use App\Services\Task\TaskService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpportunityService
{
    public function __construct(
        protected readonly GuardianService $guardianService,
        protected readonly StudentService $studentService,
        protected readonly TaskService $taskService,
    ) {}

    public function list(array $filters = [], int $perPage = 15): LengthAwarePaginator
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

        if (array_key_exists('lead_source_id', $filters) && $filters['lead_source_id'] !== null && $filters['lead_source_id'] !== '') {
            $val = $filters['lead_source_id'];
            $id = is_numeric($val) ? (int) $val : LeadSource::where('uuid', $val)->value('id');
            if ($id !== null) {
                $query->where('lead_source_id', $id);
            }
        }

        if (array_key_exists('registration_type', $filters) && $filters['registration_type'] !== null && $filters['registration_type'] !== '') {
            $query->where('registration_type', $filters['registration_type']);
        }

        if (array_key_exists('segment_id', $filters) && $filters['segment_id'] !== null && $filters['segment_id'] !== '') {
            $val = $filters['segment_id'];
            $id = is_numeric($val) ? (int) $val : Segment::where('uuid', $val)->value('id');
            if ($id !== null) {
                $query->where('segment_id', $id);
            }
        }

        if (array_key_exists('school_unit_id', $filters) && $filters['school_unit_id'] !== null && $filters['school_unit_id'] !== '') {
            $val = $filters['school_unit_id'];
            $id = is_numeric($val) ? (int) $val : SchoolUnit::where('uuid', $val)->value('id');
            if ($id !== null) {
                $query->where('school_unit_id', $id);
            }
        }

        if (array_key_exists('date_from', $filters) && $filters['date_from'] !== null && $filters['date_from'] !== '') {
            $query->whereDate('opportunities.created_at', '>=', $filters['date_from']);
        }

        if (array_key_exists('date_to', $filters) && $filters['date_to'] !== null && $filters['date_to'] !== '') {
            $query->whereDate('opportunities.created_at', '<=', $filters['date_to']);
        }

        if (array_key_exists('per_page', $filters)) {
            $perPage = (int) $filters['per_page'];
        }

        $page = (int) ($filters['page'] ?? 1);

        return $query
            ->with(['student', 'guardian', 'grade', 'segment', 'schoolYear', 'responsibleUser', 'schoolUnit'])
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function listByStatus(array $filters = [], int $perPage = 10): array
    {
        // Resolve UUIDs to IDs once, before the loop
        $resolvedFilters = $filters;

        if (array_key_exists('lead_source_id', $filters) && $filters['lead_source_id'] !== null && $filters['lead_source_id'] !== '') {
            $resolvedFilters['lead_source_id'] = LeadSource::where('uuid', $filters['lead_source_id'])->value('id');
        }

        if (array_key_exists('segment_id', $filters) && $filters['segment_id'] !== null && $filters['segment_id'] !== '') {
            $resolvedFilters['segment_id'] = Segment::where('uuid', $filters['segment_id'])->value('id');
        }

        if (array_key_exists('school_unit_id', $filters) && $filters['school_unit_id'] !== null && $filters['school_unit_id'] !== '') {
            $resolvedFilters['school_unit_id'] = SchoolUnit::where('uuid', $filters['school_unit_id'])->value('id');
        }

        $columns = [];

        foreach (OpportunityStatus::cases() as $case) {
            $statusFilters = $resolvedFilters;
            $statusFilters['status'] = $case->value;
            $statusFilters['page'] = (int) ($filters['page_'.$case->value] ?? 1);

            // Remove the individual page_{status} keys so they don't interfere
            foreach (OpportunityStatus::cases() as $c) {
                unset($statusFilters['page_'.$c->value]);
            }

            $columns[$case->value] = $this->list($statusFilters, $perPage);
        }

        return $columns;
    }

    public function create(array $data): Opportunity
    {
        $taskType = $data['task_type'] ?? null;
        unset($data['task_type']);

        $guardianId = null;

        if (! empty($data['guardian_name'])) {
            $guardianData = array_filter([
                'name' => $data['guardian_name'],
                'cpf' => $data['guardian_cpf'] ?? null,
                'phone' => $data['guardian_phone'] ?? null,
                'email' => $data['guardian_email'] ?? null,
                'zip_code' => ($data['zip_code'] ?? null) ?: null,
                'street' => $data['street'] ?? null,
                'number' => $data['number'] ?? null,
                'neighborhood' => $data['neighborhood'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
            ], fn ($v) => $v !== null && $v !== '');

            $guardian = $this->guardianService->findOrCreate($guardianData);
            $guardianId = $guardian->id;
        }

        $student = $this->studentService->findOrCreate(array_filter([
            'name' => $data['student_name'],
            'cpf' => $data['student_cpf'] ?? null,
        ], fn ($v) => $v !== null && $v !== ''));

        if ($guardianId !== null) {
            if (! $student->guardians()->where('guardian_id', $guardianId)->exists()) {
                $student->guardians()->attach($guardianId);
            }
        }

        return DB::transaction(function () use ($data, $student, $guardianId, $taskType): Opportunity {
            $opportunity = Opportunity::create([
                'student_id' => $student->id,
                'guardian_id' => $guardianId,
                'grade_id' => $data['grade_id'],
                'school_year_id' => $data['school_year_id'],
                'lead_source_id' => $data['lead_source_id'] ?? null,
                'responsible_user_id' => $data['responsible_user_id'] ?? null,
                'registration_type' => $data['registration_type'] ?? null,
                'segment_id' => $data['segment_id'] ?? null,
                'school_unit_id' => $data['school_unit_id'] ?? null,
                'history' => $data['history'] ?? null,
                'indications' => $data['indications'] ?? null,
            ]);

            if ($taskType !== null) {
                $this->taskService->create($opportunity, ['type' => TaskType::from($taskType)]);
            }

            return $opportunity;
        });
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

        if (array_key_exists('student_birth_date', $data) && $data['student_birth_date'] !== null && $opportunity->student !== null) {
            $opportunity->student->update(['date_of_birth' => $data['student_birth_date']]);
        }

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
