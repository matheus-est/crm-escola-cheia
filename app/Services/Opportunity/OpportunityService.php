<?php

declare(strict_types=1);

namespace App\Services\Opportunity;

use App\Enums\SchoolYearStatus;
use App\Models\Opportunity;
use App\Models\SchoolYear;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class OpportunityService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Opportunity::query();

        if (array_key_exists('status', $filters) && $filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }

        if (array_key_exists('grade_id', $filters) && $filters['grade_id'] !== null) {
            $query->where('grade_id', (int) $filters['grade_id']);
        }

        if (array_key_exists('school_year_id', $filters) && $filters['school_year_id'] !== null) {
            $query->where('school_year_id', (int) $filters['school_year_id']);
        }

        if (array_key_exists('responsible_user_id', $filters) && $filters['responsible_user_id'] !== null) {
            $query->where('responsible_user_id', (int) $filters['responsible_user_id']);
        }

        return $query->orderByDesc('created_at')->paginate(15);
    }

    public function create(array $data): Opportunity
    {
        return Opportunity::create($data);
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
