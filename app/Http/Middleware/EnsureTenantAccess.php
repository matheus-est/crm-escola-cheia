<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the authenticated user has access to the currently active tenant.
 *
 * Must run after SetActiveTenant, which registers the 'tenant.school_id' binding.
 *
 * Master users (cross-tenant access) are allowed through unconditionally.
 * Non-master users are verified against the active tenant school ID.
 *
 * In Etapa 0.2 (before currentSchool() exists), this middleware acts as a
 * pass-through guard: if the container binding is absent, it aborts 503.
 */
class EnsureTenantAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->bound('tenant.school_id')) {
            abort(503, 'Tenant resolution not configured');
        }

        $user = auth()->user();

        // isMaster() will be implemented alongside currentSchool() in Etapa 0.3.
        // When the method exists and returns true, allow cross-tenant access.
        if (method_exists($user, 'isMaster') && $user->isMaster()) {
            return $next($request);
        }

        // currentSchool() will be implemented in Etapa 0.3.
        // If not yet available, treat as a pass-through (binding already set by SetActiveTenant).
        if (! method_exists($user, 'currentSchool')) {
            return $next($request);
        }

        $activeSchoolId = app('tenant.school_id');
        $userSchool = $user->currentSchool();

        if ($userSchool === null || $userSchool->id !== $activeSchoolId) {
            abort(403);
        }

        return $next($request);
    }
}
