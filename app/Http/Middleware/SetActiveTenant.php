<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active tenant for the current request and binds its ID into the container.
 *
 * The school ID is derived exclusively from the authenticated user — never from
 * request input, query string, or any other user-supplied data.
 *
 * If the User model does not yet implement currentSchool() (pre-Etapa 0.3),
 * this middleware aborts with 503 to signal missing configuration.
 */
class SetActiveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // currentSchool() is implemented in Etapa 0.3.
        // Guard against missing implementation to prevent silent failures.
        if (! method_exists($user, 'currentSchool')) {
            abort(503, 'Tenant resolution not configured');
        }

        $school = $user->currentSchool();

        if ($school === null) {
            abort(403);
        }

        app()->instance('tenant.school_id', $school->id);

        return $next($request);
    }
}
