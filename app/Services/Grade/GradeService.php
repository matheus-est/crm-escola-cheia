<?php

declare(strict_types=1);

namespace App\Services\Grade;

use App\Models\Grade;
use App\Models\Segment;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class GradeService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Grade::query()->with('segment');

        if (! empty($filters['name'])) {
            $query->where('name', 'like', '%'.$filters['name'].'%');
        }

        if (! empty($filters['segment_id'])) {
            $query->whereHas('segment', function ($q) use ($filters) {
                $q->where('uuid', $filters['segment_id']);
            });
        }

        $sortBy = $filters['sort_by'] ?? 'name';
        $sortDir = $filters['sort_dir'] ?? 'asc';

        $allowedSorts = ['name', 'order'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = (int) ($filters['per_page'] ?? 10);

        return $query->paginate($perPage);
    }

    public function create(array $data): Grade
    {
        $segment = Segment::where('uuid', $data['segment_uuid'])->firstOrFail();

        return Grade::create([
            'uuid' => Str::uuid()->toString(),
            'school_id' => app('tenant.school_id') ?? auth()->user()->school_current_id,
            'segment_id' => $segment->id,
            'name' => $data['name'],
            'order' => $data['order'] ?? 0,
        ]);
    }

    public function update(Grade $grade, array $data): Grade
    {
        $segment = Segment::where('uuid', $data['segment_uuid'])->firstOrFail();

        $grade->update([
            'segment_id' => $segment->id,
            'name' => $data['name'],
            'order' => $data['order'] ?? $grade->order,
        ]);

        return $grade;
    }

    public function destroy(Grade $grade): void
    {
        $grade->delete();
    }
}
