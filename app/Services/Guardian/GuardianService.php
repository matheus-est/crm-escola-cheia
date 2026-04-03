<?php

declare(strict_types=1);

namespace App\Services\Guardian;

use App\Models\Guardian;
use App\Models\School;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class GuardianService
{
    public function lookup(School $school, string $cpf): ?Guardian
    {
        return Guardian::withoutTenantScope()
            ->where('school_id', $school->id)
            ->where('cpf', $cpf)
            ->first();
    }

    public function findOrCreate(School $school, array $data): Guardian
    {
        if (array_key_exists('cpf', $data) && $data['cpf'] !== null) {
            $existing = $this->lookup($school, $data['cpf']);

            if ($existing !== null) {
                return $existing;
            }
        }

        return $this->create($school, $data);
    }

    public function list(School $school): LengthAwarePaginator
    {
        return Guardian::query()
            ->where('school_id', $school->id)
            ->orderBy('nome')
            ->paginate(15);
    }

    public function create(School $school, array $data): Guardian
    {
        if (array_key_exists('cpf', $data) && $data['cpf'] !== null) {
            $exists = $this->lookup($school, $data['cpf']);

            if ($exists !== null) {
                throw ValidationException::withMessages([
                    'cpf' => ['Já existe um responsável com este CPF nesta escola.'],
                ]);
            }
        }

        $guardian = new Guardian($data);
        $guardian->school_id = $school->id;
        $guardian->save();

        return $guardian;
    }

    public function update(Guardian $guardian, array $data): Guardian
    {
        $guardian->fill($data);
        $guardian->save();

        return $guardian;
    }
}
