<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Acl\TermService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TermAcceptanceRequired
{
    public function __construct(
        protected TermService $termService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if (! $this->termService->getActiveTerm()) {
            return $next($request);
        }

        if (! $this->termService->needsAcceptance($user)) {
            return $next($request);
        }

        $allowedRouteNames = [
            'terms.accept',
            'terms.accept.form',
            'logout',
            'password.change',
            'password.change.store',
        ];

        $allowedPathPrefixes = [
            '/terms/',
            '/terms',
            '/logout',
            '/password',
        ];

        $currentRoute = $request->route()?->getName();
        $currentPath  = $request->path();
        
        if ($currentRoute && in_array($currentRoute, $allowedRouteNames, true)) {
            return $next($request);
        }
        
        foreach ($allowedPathPrefixes as $prefix) {
            if (str_starts_with('/' . $currentPath, $prefix)) {
                return $next($request);
            }
        }

        return to_route('terms.accept.form')
            ->with('info', 'Você precisa aceitar os novos Termos de Uso para continuar.');
    }
}