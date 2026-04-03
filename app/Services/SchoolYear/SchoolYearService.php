<?php

declare(strict_types=1);

namespace App\Services\SchoolYear;

use App\Models\School;
use App\Models\SchoolYear;
use Illuminate\Pagination\LengthAwarePaginator;

class SchoolYearService
{
    public function list(School $school, array $filters = []): LengthAwarePaginator
    {
        $query = SchoolYear::query()->where('school_id', $school->id);

        if (($filters['nome'] ?? '') !== '') {
            $query->where('nome', 'LIKE', '%'.$filters['nome'].'%');
        }

        if (($filters['status'] ?? '') !== '') {
            $query->where('status', $filters['status']);
        }

        $allowedSort = ['nome', 'inicio', 'fim', 'status'];
        $sortBy = in_array($filters['sort_by'] ?? '', $allowedSort, strict: true) ? $filters['sort_by'] : 'nome';
        $sortDir = ($filters['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = max(1, (int) ($filters['per_page'] ?? 10));

        return $query->paginate($perPage);
    }

    public function create(School $school, array $data): SchoolYear
    {
        $schoolYear = new SchoolYear($data);
        $schoolYear->school_id = $school->id;
        $schoolYear->save();

        return $schoolYear;
    }

    public function update(SchoolYear $schoolYear, array $data): SchoolYear
    {
        $schoolYear->fill($data);
        $schoolYear->save();

        return $schoolYear;
    }

    public function destroy(SchoolYear $schoolYear): void
    {
        $schoolYear->delete();
    }
}
