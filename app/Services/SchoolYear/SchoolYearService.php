<?php

declare(strict_types=1);

namespace App\Services\SchoolYear;

use App\Models\SchoolYear;
use Illuminate\Pagination\LengthAwarePaginator;

class SchoolYearService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = SchoolYear::query();

        if (array_key_exists('name', $filters) && $filters['name'] !== '') {
            $query->where('name', 'LIKE', '%'.$filters['name'].'%');
        }

        if (array_key_exists('status', $filters) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        $allowedSort = ['name', 'start', 'end', 'status'];
        $sortBy = in_array($filters['sort_by'] ?? '', $allowedSort, strict: true) ? $filters['sort_by'] : 'name';
        $sortDir = ($filters['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortBy, $sortDir);

        $perPage = max(1, (int) ($filters['per_page'] ?? 10));

        return $query->paginate($perPage);
    }

    public function create(array $data): SchoolYear
    {
        return SchoolYear::create($data);
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
