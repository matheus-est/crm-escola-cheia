<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Enums\SchoolStatus;
use App\Models\School;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ActiveSchoolService
{
    public function switch(User $user, string $schoolUuid): void
    {
        $school = School::where('uuid', $schoolUuid)->firstOrFail();

        $hasAccess = $user->isCrossTenant()
            ? $school->status === SchoolStatus::Active
            : $user->schools()
                ->where('schools.id', $school->id)
                ->wherePivot('is_active', true)
                ->exists();

        if (! $hasAccess) {
            throw ValidationException::withMessages([
                'school_uuid' => 'Você não tem acesso a esta escola.',
            ]);
        }

        session(['active_school_uuid' => $school->uuid]);

        $user->updateQuietly(['school_current_id' => $school->id]);
    }
}
