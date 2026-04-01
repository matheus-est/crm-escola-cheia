<?php

declare(strict_types=1);

namespace App\Http\Controllers\Acl;

use App\Http\Controllers\Controller;
use App\Http\Requests\TermStoreRequest;
use App\Http\Requests\TermUpdateRequest;
use App\Services\Acl\TermService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TermController extends Controller
{
    public function __construct(
        protected readonly TermService $termService,
    ) {}

    public function index(Request $request): Response|RedirectResponse
    {
        $filters = session('terms_filters', [
            'sort_by'  => 'created_at',
            'sort_dir' => 'desc',
            'per_page' => 10,
        ]);

        if ($request->isMethod('POST')) {
            $filters = $request->only(['sort_by', 'sort_dir', 'per_page']);
            session(['terms_filters' => $filters]);

            return to_route('terms.index');
        }

        return Inertia::render('acl/Terms/Index', [
            'terms'   => $this->termService->filter($filters),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('acl/Terms/Edit', [
            'term'        => null,
            'nextVersion' => $this->termService->getNextVersion(),
        ]);
    }

    public function store(TermStoreRequest $request): RedirectResponse
    {
        $this->termService->createTermVersion(
            $request->validated(),
            $request->user(),
        );

        return to_route('terms.index')
            ->with('success', 'Versão dos termos criada com sucesso.');
    }

    public function edit(string $termUuid): Response
    {
        return Inertia::render('acl/Terms/Edit', [
            'term'        => $this->termService->getTermByUuid($termUuid),
            'nextVersion' => $this->termService->getNextVersion(),
        ]);
    }

    public function update(TermUpdateRequest $request, string $termUuid): RedirectResponse
    {
        $term = $this->termService->getTermByUuid($termUuid);

        $this->termService->updateTermVersion($term, $request->validated());

        return to_route('terms.index')
            ->with('success', 'Versão dos termos atualizada com sucesso.');
    }

    public function destroy(Request $request, string $termUuid): RedirectResponse
    {
        $term = $this->termService->getTermByUuid($termUuid);

        if (! $this->termService->isTermDeletable($term)) {
            abort(403, 'Não é possível excluir este termo.');
        }

        $this->termService->deleteTermVersion($term, $request->user());

        return to_route('terms.index')
            ->with('success', 'Versão dos termos excluída com sucesso.');
    }

    public function accept(Request $request, string $termUuid): RedirectResponse
    {
        if (! $this->termService->canAcceptTerm($termUuid)) {
            abort(400, 'Este termo não está ativo.');
        }

        $term = $this->termService->getTermByUuid($termUuid);

        $this->termService->acceptTerm(
            $request->user(),
            $term->id,
            $request->ip(),
            $request->userAgent(),
        );

        return to_route('dashboard')
            ->with('success', 'Termos aceitos com sucesso. Bem-vindo!');
    }

    public function showAcceptForm(Request $request): Response
    {
        $activeTerm = $this->termService->getActiveTerm();
        $acceptance = $activeTerm
            ? $this->termService->getUserAcceptance($request->user())
            : null;

        return Inertia::render('settings/TermAccept', [
            'term'       => $activeTerm,
            'acceptance' => $acceptance,
        ]);
    }
}