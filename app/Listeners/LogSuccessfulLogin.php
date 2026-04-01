<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Http\Middleware\EnforceSingleSession;
use App\Listeners\Concerns\DetectsLoginType;
use App\Mail\SessionInvalidatedMail;
use App\Models\User;
use App\Services\Acl\AuditLoginService;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class LogSuccessfulLogin
{
    use DetectsLoginType;

    public function __construct(
        protected readonly AuditLoginService $auditService,
        protected readonly Request $request,
    ) {}

    public function handle(Login $event): void
    {
        $cacheKey = sprintf(
            'audit_login_success_%d_%s',
            $event->user->id,
            $this->request->ip() ?? 'unknown'
        );

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->addSeconds(5));
        $this->invalidatePreviousSessions($event->user);
        EnforceSingleSession::markNewLogin($event->user->id);

        $this->auditService->createAudit(
            userId:    $event->user->id,
            ipAddress: $this->request->ip(),
            success:   true,
            loginType: $this->detectLoginType($this->request),
            userAgent: $this->request->userAgent(),
            sessionId: $this->request->session()->getId(),
        );
    }

    private function invalidatePreviousSessions(User $user): void
    {
        $invalidatedSessionIds = $this->auditService->invalidateOtherSessions(
            $user->id,
            '__new_login__'
        );

        if (empty($invalidatedSessionIds)) {
            return;
        }

        DB::table('sessions')
            ->whereIn('id', $invalidatedSessionIds)
            ->delete();

        Mail::to($user->email)->queue(new SessionInvalidatedMail($user, $this->request));
    }
}