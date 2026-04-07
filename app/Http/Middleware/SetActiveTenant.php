<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\SchoolStatus;
use App\Models\School;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SetActiveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user === null) {
            return $next($request);
        }

        if (! method_exists($user, 'currentSchool')) {
            abort(503, 'Tenant resolution not configured');
        }

        $school = $this->resolveSchool($user);

        if ($school === null) {
            if ($user->isCrossTenant()) {
                return $next($request);
            }
            abort(403);
        }

        session(['active_school_uuid' => $school->uuid]);
        app()->instance('tenant.school_id', $school->id);

        return $next($request);
    }

    private function resolveSchool($user): ?School
    {
        $sessionUuid = session('active_school_uuid');

        if ($sessionUuid) {
            $query = School::query()
                ->where('uuid', $sessionUuid)
                ->where('status', SchoolStatus::Active);

            if ($user->isCrossTenant()) {
                return $query->first();
            }

            return $user->schools()
                ->where('schools.uuid', $sessionUuid)
                ->where('schools.status', SchoolStatus::Active)
                ->wherePivot('is_active', true)
                ->first();
        }

        if ($user->school_current_id !== null) {
            $school = School::query()->where('id', $user->school_current_id)->first();

            if ($school && $school->status === SchoolStatus::Active) {
                session(['active_school_uuid' => $school->uuid]);

                return $school;
            }
        }

        if (! $user->isCrossTenant()) {
            return $user->schools()
                ->wherePivot('is_active', true)
                ->where('schools.status', SchoolStatus::Active)
                ->first();
        }

        return null;
    }
}