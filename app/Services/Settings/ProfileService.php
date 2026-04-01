<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Models\AuditLogin;
use App\Models\User;
use App\Services\SettingService;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    public function __construct(
        protected readonly SettingService $settingService,
    ) {}

    /**
     * Update the user's profile information.
     */
    public function update(User $user, array $data): void
    {
        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
    }

    /**
     * Permanently delete the user's account.
     */
    public function deleteAccount(User $user): void
    {
        if (! $this->settingService->get('allow_self_deletion', false)) {
            throw ValidationException::withMessages([
                'password' => __('Account deletion is not allowed.'),
            ]);
        }

        $user->forceDelete();
    }

    /**
     * Export the user's personal data (LGPD).
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
                'email_verified_at' => $user->email_verified_at,
            ],
            'login_audits' => AuditLogin::query()
                ->where('user_id', $user->id)
                ->orderBy('logged_in_at', 'desc')
                ->limit(100)
                ->get()
                ->map(fn ($audit) => [
                    'ip_address' => $audit->ip_address,
                    'browser' => $audit->browser,
                    'os' => $audit->os,
                    'device' => $audit->device,
                    'login_type' => $audit->login_type,
                    'logged_in_at' => $audit->logged_in_at,
                    'success' => $audit->success,
                ]),
        ];
    }
}
