<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user->isCrossTenant()) {
            return $next($request);
        }

        if (! app()->bound('tenant.school_id')) {
            abort(503, 'Tenant resolution not configured');
        }

        $hasAccess = $user->schools()
            ->where('schools.id', app('tenant.school_id'))
            ->wherePivot('is_active', true)
            ->exists();

        if (! $hasAccess) {
            abort(403);
        }

        return $next($request);
    }
}