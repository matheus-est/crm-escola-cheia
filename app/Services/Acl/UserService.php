<?php

declare(strict_types=1);

namespace App\Services\Acl;

use App\Mail\UserReactivatedMail;
use App\Mail\WelcomeUserMail;
use App\Models\AuditLogin;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(
        protected readonly AuditLoginService $auditLoginService,
        protected readonly SettingService $settingService,
    ) {}

    /**
     * Returns all users for internal/select use only (not a paginated UI listing).
     * Limited to 500 records to prevent OOM.
     */
    public function getAll(?User $currentUser = null, string $status = 'active'): Collection
    {
        return User::with('role')
            ->when($status === 'trashed', fn ($q) => $q->onlyTrashed(), fn ($q) => $q->whereNull('deleted_at'))
            ->when(
                $currentUser && ! $this->isMaster($currentUser),
                fn ($q) => $q->whereHas('role', fn ($q) => $q->where('name', '!=', 'Master'))
            )
            ->orderBy('name')
            ->limit(500)
            ->get();
    }

    public function getById(int $id): User
    {
        return User::with('role')->findOrFail($id);
    }

    public function getByUuid(string $uuid): User
    {
        return User::with('role')->where('uuid', $uuid)->firstOrFail();
    }

    public function getByUuidWithTrashed(string $uuid): User
    {
        return User::withTrashed()->with('role')->where('uuid', $uuid)->firstOrFail();
    }

    public function filter(array $filters, ?User $currentUser = null): LengthAwarePaginator
    {
        $status = $filters['status'] ?? 'active';
        $sortBy = in_array($filters['sort_by'] ?? 'name', ['name', 'email']) ? $filters['sort_by'] : 'name';
        $sortDir = ($filters['sort_dir'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = $filters['per_page'] ?? 10;
        $perPage = $perPage === 'all' || (int) $perPage === 0 ? PHP_INT_MAX : (int) $perPage;

        return User::with('role')
            ->when($status === 'trashed', fn ($q) => $q->onlyTrashed(), fn ($q) => $q->whereNull('deleted_at'))
            ->when(! empty($filters['name']), fn ($q) => $q->where('name', 'like', '%'.$filters['name'].'%'))
            ->when(! empty($filters['email']), fn ($q) => $q->where('email', 'like', '%'.$filters['email'].'%'))
            ->when(! empty($filters['role_id']), fn ($q) => $q->where('role_id', $filters['role_id']))
            ->when(
                $currentUser && ! $this->isMaster($currentUser),
                fn ($q) => $q->whereHas('role', fn ($q) => $q->where('name', '!=', 'Master'))
            )
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
    }

    public function checkEmailExists(string $email): ?User
    {
        return User::withTrashed()->where('email', $email)->first();
    }

    public function create(array $data): User
    {
        $temporaryPassword = $this->generateTemporaryPassword();

        return DB::transaction(function () use ($data, $temporaryPassword) {
            $existingUser = User::withTrashed()->where('email', $data['email'])->first();

            if ($existingUser?->trashed()) {
                $existingUser->restore();
                $existingUser->update([
                    'name' => $data['name'],
                    'password' => Hash::make($temporaryPassword),
                    'role_id' => $data['role_id'],
                    'password_changed_at' => null,
                    'email_verified_at' => now(),
                ]);

                $existingUser->load('role');
                $this->sendWelcomeEmail($existingUser, $temporaryPassword);

                return $existingUser;
            }

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($temporaryPassword),
                'role_id' => $data['role_id'],
                'email_verified_at' => now(),
            ]);

            $user->load('role');
            $this->sendWelcomeEmail($user, $temporaryPassword);

            return $user;
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'role_id' => $data['role_id'],
            ]);

            if (! empty($data['password'])) {
                $user->update(['password' => Hash::make($data['password'])]);
            }

            return $user;
        });
    }

    public function delete(User $user, User $currentUser): void
    {
        if (! $this->isMaster($currentUser) && $user->role?->name === 'Master') {
            throw new \Exception('Você não tem permissão para excluir este usuário.');
        }

        $user->delete();
    }

    public function confirmDelete(User $user, string $password, User $currentUser): bool
    {
        return DB::transaction(function () use ($user, $password, $currentUser) {
            if (! Hash::check($password, $currentUser->password)) {
                $validator = Validator::make(['password' => $password], ['password' => 'required']);
                $validator->errors()->add('password', 'Senha incorreta.');
                throw new ValidationException($validator);
            }

            $this->delete($user, $currentUser);

            return true;
        });
    }

    public function restore(User $user): User
    {
        $temporaryPassword = $this->generateTemporaryPassword();

        $user->restore();
        $user->update([
            'password' => Hash::make($temporaryPassword),
            'password_changed_at' => null,
            'email_verified_at' => now(),
        ]);

        $user->load('role');
        $this->sendReactivationEmail($user, $temporaryPassword);

        return $user;
    }

    public function canRestore(User $currentUser): bool
    {
        return $this->isMaster($currentUser);
    }

    public function confirmRestore(string $userUuid, string $password, User $currentUser): User
    {
        if (! Hash::check($password, $currentUser->password)) {
            $validator = Validator::make(['password' => $password], ['password' => 'required']);
            $validator->errors()->add('password', 'Senha incorreta.');
            throw new ValidationException($validator);
        }

        return $this->restore($this->getByUuidWithTrashed($userUuid));
    }

    public function forcePasswordChange(User $user, string $newPassword): void
    {
        $user->update([
            'password' => Hash::make($newPassword),
            'password_changed_at' => now(),
        ]);
    }

    public function mustChangePassword(User $user): bool
    {
        if ($user->password_changed_at === null) {
            return true;
        }

        $expirationDays = (int) $this->settingService->get('password_expiration_days', 0);

        if ($expirationDays <= 0) {
            return false;
        }

        return $user->password_changed_at->addDays($expirationDays)->isPast();
    }

    public function canAccessUser(User $currentUser, User $target): bool
    {
        if ($this->isMaster($currentUser)) {
            return true;
        }

        return $target->role?->name !== 'Master';
    }

    public function getLoginAudits(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return $this->auditLoginService->getByUser($userId, $perPage);
    }

    /**
     * Export user data for LGPD compliance.
     *
     * @return array<string, mixed>
     */
    public function exportData(User $user): array
    {
        return [
            'user' => [
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->name,
                'created_at' => $user->created_at,
                'deleted_at' => $user->deleted_at,
                'email_verified_at' => $user->email_verified_at,
                'password_changed_at' => $user->password_changed_at,
                'two_factor_enabled' => ! empty($user->two_factor_secret),
            ],
            'login_audits' => AuditLogin::query()
                ->where('user_id', $user->id)
                ->orderBy('logged_in_at', 'desc')
                ->limit(500)
                ->get()
                ->map(fn ($audit) => [
                    'ip_address' => $audit->ip_address,
                    'browser' => $audit->browser,
                    'browser_version' => $audit->browser_version,
                    'os' => $audit->os,
                    'device' => $audit->device,
                    'login_type' => $audit->login_type,
                    'logged_in_at' => $audit->logged_in_at,
                    'logged_out_at' => $audit->logged_out_at,
                    'success' => $audit->success,
                    'failure_reason' => $audit->failure_reason,
                ]),
        ];
    }

    public function hasPermission(User $user, string $permission): bool
    {
        return $user->role?->permissions()->where('name', $permission)->exists() ?? false;
    }

    public function hasAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($user, $permission)) {
                return true;
            }
        }

        return false;
    }

    public function hasAllPermissions(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->hasPermission($user, $permission)) {
                return false;
            }
        }

        return true;
    }

    public function canAccess(User $user, string $module, string $action): bool
    {
        return $this->hasPermission($user, "{$module}_{$action}");
    }

    private function isMaster(User $user): bool
    {
        return $user->role?->name === 'Master';
    }

    private function generateTemporaryPassword(int $length = 12): string
    {
        return Str::random($length);
    }

    private function sendWelcomeEmail(User $user, string $temporaryPassword): void
    {
        Mail::to($user->email)->send(new WelcomeUserMail($user, $temporaryPassword));
    }

    private function sendReactivationEmail(User $user, string $temporaryPassword): void
    {
        Mail::to($user->email)->send(new UserReactivatedMail($user, $temporaryPassword));
    }
}
