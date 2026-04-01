<?php

declare(strict_types=1);

namespace App\Services\Acl;

use App\Models\AuditLogin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Jenssegers\Agent\Agent;

class AuditLoginService
{
    public function createAudit(
        ?int $userId,
        ?string $ipAddress,
        bool $success,
        ?string $failureReason = null,
        ?string $loginType = null,
        ?string $userAgent = null,
        ?string $sessionId = null,
    ): AuditLogin {
        $browser = null;
        $browserVersion = null;
        $os = null;
        $device = null;

        if ($userAgent) {
            $agent = new Agent;
            $agent->setUserAgent($userAgent);

            $browser = $agent->browser() ?: null;
            $browserVersion = $agent->version($agent->browser() ?: '') ?: null;
            $os = $agent->platform() ?: null;
            $device = match (true) {
                $agent->isTablet() => 'tablet',
                $agent->isMobile() => 'mobile',
                $agent->isDesktop() => 'desktop',
                default => null,
            };
        }

        return AuditLogin::create([
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'login_type' => $loginType,
            'browser' => $browser,
            'browser_version' => $browserVersion,
            'os' => $os,
            'device' => $device,
            'session_id' => $sessionId,
            'logged_in_at' => now(),
            'success' => $success,
            'failure_reason' => $failureReason,
        ]);
    }

    public function markLogout(?string $sessionId): void
    {
        if (! $sessionId) {
            return;
        }

        AuditLogin::where('session_id', $sessionId)
            ->whereNull('logged_out_at')
            ->latest('logged_in_at')
            ->first()
            ?->update(['logged_out_at' => now()]);
    }

    /**
     * Invalida todas as sessões ativas de um usuário exceto a atual.
     * Retorna os session_ids das sessões derrubadas.
     *
     * @return array<string>
     */
    public function invalidateOtherSessions(int $userId, string $currentSessionId): array
    {
        $activeSessions = AuditLogin::where('user_id', $userId)
            ->where('success', true)
            ->whereNull('logged_out_at')
            ->where('session_id', '!=', $currentSessionId)
            ->whereNotNull('session_id')
            ->get();

        $sessionIds = $activeSessions->pluck('session_id')->filter()->values()->toArray();

        $activeSessions->each(fn ($audit) => $audit->update(['logged_out_at' => now()]));

        return $sessionIds;
    }

    public function getActiveSessionsCount(int $userId): int
    {
        return AuditLogin::where('user_id', $userId)
            ->where('success', true)
            ->whereNull('logged_out_at')
            ->count();
    }

    public function getByUser(int $userId, int $perPage = 10): LengthAwarePaginator
    {
        return AuditLogin::where('user_id', $userId)
            ->orderBy('logged_in_at', 'desc')
            ->paginate($perPage);
    }

    public function isActive(AuditLogin $auditLogin): bool
    {
        return $auditLogin->success && $auditLogin->logged_out_at === null;
    }
}
