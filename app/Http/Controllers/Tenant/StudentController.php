<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StudentStoreRequest;
use App\Http\Requests\Student\StudentUpdateRequest;
use App\Models\Guardian;
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

    public function index(): Response
    {
        Gate::authorize('viewAny', Student::class);

        $students = $this->studentService->list();

        return Inertia::render('students/Index', [
            'students' => $students,
        ]);
    }

    public function store(StudentStoreRequest $request): RedirectResponse
    {
        Gate::authorize('create', Student::class);

        $this->studentService->create($request->validated());

        return to_route('tenant.students.index')
            ->with('success', 'Aluno criado com sucesso.');
    }

    public function update(StudentUpdateRequest $request, Student $student): RedirectResponse
    {
        Gate::authorize('update', $student);

        $this->studentService->update($student, $request->validated());

        return to_route('tenant.students.index')
            ->with('success', 'Aluno atualizado com sucesso.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        Gate::authorize('delete', $student);

        $student->delete();

        return to_route('tenant.students.index')
            ->with('success', 'Aluno excluído com sucesso.');
    }

    public function lookup(string $cpf): JsonResponse
    {
        Gate::authorize('viewAny', Student::class);

        $isGuardian = Guardian::where('cpf', $cpf)->exists();
        if ($isGuardian) {
            return response()->json([
                'error' => 'found_as_guardian',
                'message' => 'O CPF informado pertence a um responsável cadastrado.',
            ], 409);
        }

        $student = $this->studentService->lookup($cpf);

        if ($student === null) {
            return response()->json(['message' => 'Aluno não encontrado.'], 404);
        }

        return response()->json(array_merge(
            $student->toArray(),
            ['guardian' => $student->guardians->first()?->toArray()]
        ));
    }
}
