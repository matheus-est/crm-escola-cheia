<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StudentStoreRequest;
use App\Http\Requests\Student\StudentUpdateRequest;
use App\Models\School;
use App\Models\Student;
use App\Services\Student\StudentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function __construct(
        protected readonly StudentService $studentService,
    ) {}

    public function index(School $school): Response
    {
        Gate::authorize('viewAny', Student::class);

        $students = $this->studentService->list($school);

        return Inertia::render('students/Index', [
            'school' => $school,
            'students' => $students,
        ]);
    }

    public function store(StudentStoreRequest $request, School $school): RedirectResponse
    {
        Gate::authorize('create', Student::class);

        $this->studentService->create($school, $request->validated());

        return to_route('tenant.students.index', $school)
            ->with('success', 'Aluno criado com sucesso.');
    }

    public function update(StudentUpdateRequest $request, School $school, Student $student): RedirectResponse
    {
        Gate::authorize('update', $student);

        $this->studentService->update($student, $request->validated());

        return to_route('tenant.students.index', $school)
            ->with('success', 'Aluno atualizado com sucesso.');
    }

    public function destroy(School $school, Student $student): RedirectResponse
    {
        Gate::authorize('delete', $student);

        $student->delete();

        return to_route('tenant.students.index', $school)
            ->with('success', 'Aluno excluído com sucesso.');
    }

    public function lookup(School $school, string $cpf): JsonResponse
    {
        Gate::authorize('viewAny', Student::class);

        $student = $this->studentService->lookup($school, $cpf);

        if ($student === null) {
            return response()->json(['message' => 'Aluno não encontrado.'], 404);
        }

        return response()->json($student);
    }
}
