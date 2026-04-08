<?php

declare(strict_types=1);

namespace App\Services\Guardian;

use App\Models\Guardian;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class GuardianService
{
    public function lookup(string $cpf): ?Guardian
    {
        $schoolId = app()->bound('tenant.school_id') ? app('tenant.school_id') : null;

        return Guardian::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->where('cpf', $cpf)
            ->first();
    }

    public function findOrCreate(array $data): Guardian
    {
        if (array_key_exists('cpf', $data) && $data['cpf'] !== null) {
            $existing = $this->lookup($data['cpf']);

            if ($existing !== null) {
                $existing->fill(array_filter($data, fn($v) => $v !== null && $v !== ''));
                $existing->save();
                
                return $existing;
            }
        }

        return Guardian::create($data);
    }

    public function list(): LengthAwarePaginator
    {
        return Guardian::query()
            ->orderBy('nome')
            ->paginate(15);
    }

    public function create(array $data): Guardian
    {
        if (array_key_exists('cpf', $data) && $data['cpf'] !== null) {
            $exists = $this->lookup($data['cpf']);

            if ($exists !== null) {
                throw ValidationException::withMessages([
                    'cpf' => ['Já existe um responsável com este CPF nesta escola.'],
                ]);
            }
        }

        return Guardian::create($data);
    }

    public function update(Guardian $guardian, array $data): Guardian
    {
        $guardian->fill($data);
        $guardian->save();

        return $guardian;
    }
}
