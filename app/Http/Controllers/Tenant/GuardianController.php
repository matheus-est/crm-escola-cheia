<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guardian\GuardianStoreRequest;
use App\Http\Requests\Guardian\GuardianUpdateRequest;
use App\Models\Guardian;
use App\Models\School;
use App\Services\Guardian\GuardianService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GuardianController extends Controller
{
    public function __construct(
        protected readonly GuardianService $guardianService,
    ) {}

    public function index(School $school): Response
    {
        Gate::authorize('viewAny', Guardian::class);

        $guardians = $this->guardianService->list($school);

        return Inertia::render('guardians/Index', [
            'school' => $school,
            'guardians' => $guardians,
        ]);
    }

    public function store(GuardianStoreRequest $request, School $school): RedirectResponse
    {
        Gate::authorize('create', Guardian::class);

        $this->guardianService->create($school, $request->validated());

        return to_route('tenant.guardians.index', $school)
            ->with('success', 'Responsável criado com sucesso.');
    }

    public function update(GuardianUpdateRequest $request, School $school, Guardian $guardian): RedirectResponse
    {
        Gate::authorize('update', $guardian);

        $this->guardianService->update($guardian, $request->validated());

        return to_route('tenant.guardians.index', $school)
            ->with('success', 'Responsável atualizado com sucesso.');
    }

    public function destroy(School $school, Guardian $guardian): RedirectResponse
    {
        Gate::authorize('delete', $guardian);

        $guardian->delete();

        return to_route('tenant.guardians.index', $school)
            ->with('success', 'Responsável excluído com sucesso.');
    }

    public function lookup(School $school, string $cpf): JsonResponse
    {
        Gate::authorize('viewAny', Guardian::class);

        $guardian = $this->guardianService->lookup($school, $cpf);

        if ($guardian === null) {
            return response()->json(['message' => 'Responsável não encontrado.'], 404);
        }

        return response()->json($guardian);
    }
}
