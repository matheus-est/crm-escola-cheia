<?php

declare(strict_types=1);

namespace App\Services\Student;

use App\Models\Student;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class StudentService
{
    public function lookup(string $cpf): ?Student
    {
        $schoolId = app()->bound('tenant.school_id') ? app('tenant.school_id') : null;

        return Student::withoutTenantScope()
            ->where('school_id', $schoolId)
            ->where('cpf', $cpf)
            ->first();
    }

    public function findOrCreate(array $data): Student
    {
        if (array_key_exists('cpf', $data) && $data['cpf'] !== null) {
            $existing = $this->lookup($data['cpf']);

            if ($existing !== null) {
                return $existing;
            }
        }

        return $this->create($data);
    }

    public function list(): LengthAwarePaginator
    {
        return Student::query()
            ->orderBy('nome')
            ->paginate(15);
    }

    public function create(array $data): Student
    {
        if (array_key_exists('cpf', $data) && $data['cpf'] !== null) {
            $exists = $this->lookup($data['cpf']);

            if ($exists !== null) {
                throw ValidationException::withMessages([
                    'cpf' => ['Já existe um aluno com este CPF nesta escola.'],
                ]);
            }
        }

        return Student::create($data);
    }

    public function update(Student $student, array $data): Student
    {
        $student->fill($data);
        $student->save();

        return $student;
    }
}
