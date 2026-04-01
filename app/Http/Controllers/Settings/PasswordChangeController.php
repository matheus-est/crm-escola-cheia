<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForcePasswordChangeRequest;
use App\Services\Acl\TermService;
use App\Services\Acl\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PasswordChangeController extends Controller
{
    public function __construct(
        protected readonly UserService $userService,
        protected readonly TermService $termService,
    ) {}

    public function show(Request $request): Response
    {
        $user        = $request->user();
        $activeTerm  = $this->termService->getActiveTerm();

        return Inertia::render('settings/ForcePasswordChange', [
            'currentTerm'         => $activeTerm,
            'needsTermAcceptance' => $activeTerm
                ? ! $this->termService->hasAcceptedCurrentTerm($user)
                : false,
        ]);
    }

    public function update(ForcePasswordChangeRequest $request): RedirectResponse
    {
        $user = $request->user();

        $this->userService->forcePasswordChange($user, $request->input('password'));

        // Salva aceite do termo junto com a troca de senha
        $activeTerm = $this->termService->getActiveTerm();

        if ($activeTerm && ! $this->termService->hasAcceptedCurrentTerm($user)) {
            $this->termService->acceptTerm(
                $user,
                $activeTerm->id,
                $request->ip(),
                $request->userAgent(),
            );
        }

        return to_route('dashboard')
            ->with('success', 'Senha alterada com sucesso. Bem-vindo ao sistema!');
    }
}