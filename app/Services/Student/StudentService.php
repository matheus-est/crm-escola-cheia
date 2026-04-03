<?php

declare(strict_types=1);

namespace App\Services\Student;

use App\Models\School;
use App\Models\Student;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class StudentService
{
    public function lookup(School $school, string $cpf): ?Student
    {
        return Student::withoutTenantScope()
            ->where('school_id', $school->id)
            ->where('cpf', $cpf)
            ->first();
    }

    public function findOrCreate(School $school, array $data): Student
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
        return Student::query()
            ->where('school_id', $school->id)
            ->orderBy('nome')
            ->paginate(15);
    }

    public function create(School $school, array $data): Student
    {
        if (array_key_exists('cpf', $data) && $data['cpf'] !== null) {
            $exists = $this->lookup($school, $data['cpf']);

            if ($exists !== null) {
                throw ValidationException::withMessages([
                    'cpf' => ['Já existe um aluno com este CPF nesta escola.'],
                ]);
            }
        }

        $student = new Student($data);
        $student->school_id = $school->id;
        $student->save();

        return $student;
    }

    public function update(Student $student, array $data): Student
    {
        $student->fill($data);
        $student->save();

        return $student;
    }
}
