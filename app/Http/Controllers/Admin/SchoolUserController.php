<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\SchoolUserAttachRequest;
use App\Http\Requests\School\SchoolUserCreateRequest;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use App\Services\School\SchoolService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SchoolUserController extends Controller
{
    public function __construct(protected readonly SchoolService $schoolService) {}

    public function store(School $school, SchoolUserAttachRequest $request): RedirectResponse
    {
        Gate::authorize('update', $school);

        $user = User::query()->where('uuid', $request->validated('user_id'))->firstOrFail();
        $role = Role::query()->where('uuid', $request->validated('role_id'))->firstOrFail();

        $this->schoolService->attachUser($school, $user, $role);

        return to_route('admin.schools.edit', $school);
    }

    public function storeOrCreate(School $school, SchoolUserCreateRequest $request): RedirectResponse
    {
        Gate::authorize('update', $school);

        $validated = $request->validated();
        $role = Role::query()->where('uuid', $validated['role_id'])->firstOrFail();

        $user = User::withTrashed()->where('email', $validated['email'])->first();

        if ($user !== null) {
            if ($user->trashed()) {
                $user->restore();
            }
            $this->schoolService->attachUser($school, $user, $role);
        } else {
            $this->schoolService->createUserForSchool($validated, $school, $role);
        }

        return to_route('admin.schools.edit', $school)
            ->with('success', 'Responsável adicionado com sucesso.');
    }

    public function destroy(School $school, string $userUuid): RedirectResponse
    {
        Gate::authorize('update', $school);

        $user = User::query()->where('uuid', $userUuid)->firstOrFail();

        $this->schoolService->detachUser($school, $user);

        return to_route('admin.schools.edit', $school);
    }
}
