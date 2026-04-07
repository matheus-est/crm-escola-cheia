<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Guardian\GuardianStoreRequest;
use App\Http\Requests\Guardian\GuardianUpdateRequest;
use App\Models\Guardian;
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

    public function index(): Response
    {
        Gate::authorize('viewAny', Guardian::class);

        $guardians = $this->guardianService->list();

        return Inertia::render('guardians/Index', [
            'guardians' => $guardians,
        ]);
    }

    public function store(GuardianStoreRequest $request): RedirectResponse
    {
        Gate::authorize('create', Guardian::class);

        $this->guardianService->create($request->validated());

        return to_route('tenant.guardians.index')
            ->with('success', 'Responsável criado com sucesso.');
    }

    public function update(GuardianUpdateRequest $request, Guardian $guardian): RedirectResponse
    {
        Gate::authorize('update', $guardian);

        $this->guardianService->update($guardian, $request->validated());

        return to_route('tenant.guardians.index')
            ->with('success', 'Responsável atualizado com sucesso.');
    }

    public function destroy(Guardian $guardian): RedirectResponse
    {
        Gate::authorize('delete', $guardian);

        $guardian->delete();

        return to_route('tenant.guardians.index')
            ->with('success', 'Responsável excluído com sucesso.');
    }

    public function lookup(string $cpf): JsonResponse
    {
        Gate::authorize('viewAny', Guardian::class);

        $guardian = $this->guardianService->lookup($cpf);

        if ($guardian === null) {
            return response()->json(['message' => 'Responsável não encontrado.'], 404);
        }

        return response()->json($guardian);
    }
}
