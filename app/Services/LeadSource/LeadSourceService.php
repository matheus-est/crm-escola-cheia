<?php

declare(strict_types=1);

namespace App\Services\LeadSource;

use App\Models\LeadSource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class LeadSourceService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $schoolId = app()->bound('tenant.school_id') ? app('tenant.school_id') : null;

        $query = LeadSource::query()
            ->where(function ($q) use ($schoolId): void {
                $q->where('school_id', $schoolId)
                    ->orWhere('is_system', true);
            });

        if (($filters['nome'] ?? '') !== '') {
            $query->where('nome', 'LIKE', '%'.$filters['nome'].'%');
        }

        $allowedSort = ['nome'];
        $sortBy = in_array($filters['sort_by'] ?? '', $allowedSort, strict: true) ? $filters['sort_by'] : 'nome';
        $sortDir = ($filters['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $query->orderBy('is_system', 'desc')->orderBy($sortBy, $sortDir);

        $perPage = max(1, (int) ($filters['per_page'] ?? 10));

        return $query->paginate($perPage);
    }

    public function create(array $data): LeadSource
    {
        $leadSource = new LeadSource($data);
        $leadSource->school_id = app()->bound('tenant.school_id') ? app('tenant.school_id') : null;
        $leadSource->is_system = false;
        $leadSource->save();

        return $leadSource;
    }

    public function update(LeadSource $leadSource, array $data): LeadSource
    {
        if ($leadSource->is_system) {
            throw ValidationException::withMessages([
                'is_system' => 'Origens de sistema não podem ser editadas.',
            ]);
        }

        $leadSource->fill($data);
        $leadSource->save();

        return $leadSource;
    }

    public function destroy(LeadSource $leadSource): void
    {
        if ($leadSource->is_system) {
            throw ValidationException::withMessages([
                'is_system' => 'Origens de sistema não podem ser excluídas.',
            ]);
        }

        $leadSource->delete();
    }
}
