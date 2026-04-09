<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\SchoolStoreRequest;
use App\Http\Requests\School\SchoolUpdateRequest;
use App\Models\Role;
use App\Models\School;
use App\Services\School\SchoolService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class SchoolController extends Controller
{
    public function __construct(
        protected readonly SchoolService $schoolService,
    ) {}

    public function index(Request $request): Response
    {
        $filters = $this->setFilters($request);

        $schools = $this->schoolService->list($filters);

        return Inertia::render('admin/Schools/Index', [
            'schools' => $schools,
            'filters' => $filters,
            'isMaster' => $request->user()?->role?->name === 'Master',
        ]);
    }

    public function setFilters(Request $request): array
    {
        $filters = [
            'legal_name' => $request->input('legal_name', ''),
            'unit' => $request->input('unit', ''),
            'cnpj' => $request->input('cnpj', ''),
            'status' => $request->input('status', ''),
            'sort_by' => $request->input('sort_by', 'legal_name'),
            'sort_dir' => $request->input('sort_dir', 'asc'),
            'per_page' => $request->input('per_page', 10),
        ];

        session(['school_filters' => $filters]);

        return $filters;
    }

    public function clearFilters(): RedirectResponse
    {
        session()->forget('school_filters');

        return to_route('admin.schools.index');
    }

    public function create(): Response
    {
        return Inertia::render('admin/Schools/Create', [
            'availableRoles' => Role::query()
                ->whereIn('name', ['Gestor', 'Comercial'])
                ->get(['uuid', 'name']),
        ]);
    }

    public function store(SchoolStoreRequest $request): RedirectResponse
    {
        Gate::authorize('create', School::class);

        $school = $this->schoolService->create($request->validated());

        return to_route('admin.schools.edit', $school)
            ->with('success', 'Escola criada com sucesso.');
    }

    public function edit(School $school): Response
    {
        Gate::authorize('view', $school);

        return Inertia::render('admin/Schools/Edit', [
            'school' => $school->load(['units', 'users.role']),
            'availableRoles' => Role::query()
                ->whereIn('name', ['Gestor', 'Comercial'])
                ->get(['uuid', 'name']),
        ]);
    }

    public function lookupCnpj(string $cnpj): JsonResponse
    {
        try {
            $data = $this->schoolService->lookupCnpj($cnpj);

            return response()->json($data);
        } catch (\RuntimeException $e) {
            throw ValidationException::withMessages([
                'cnpj' => $e->getMessage(),
            ]);
        }
    }

    public function update(SchoolUpdateRequest $request, School $school): RedirectResponse
    {
        Gate::authorize('update', $school);

        $this->schoolService->update($school, $request->validated());

        return to_route('admin.schools.edit', $school)
            ->with('success', 'Escola atualizada com sucesso.');
    }

    public function destroy(School $school): RedirectResponse
    {
        Gate::authorize('delete', $school);

        $this->schoolService->destroy($school);

        return to_route('admin.schools.index')
            ->with('success', 'Escola excluída com sucesso.');
    }

    public function confirmDelete(Request $request, School $school): RedirectResponse
    {
        Gate::authorize('delete', $school);

        if (! Hash::check($request->input('password'), $request->user()->password)) {
            throw ValidationException::withMessages([
                'password' => 'Senha incorreta.',
            ]);
        }

        $this->schoolService->destroy($school);

        return to_route('admin.schools.index')
            ->with('success', 'Escola excluída com sucesso.');
    }
}
