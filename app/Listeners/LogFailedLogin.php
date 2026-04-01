<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Listeners\Concerns\DetectsLoginType;
use App\Models\User;
use App\Services\Acl\AuditLoginService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class LogFailedLogin
{
    use DetectsLoginType;

    public function __construct(
        protected readonly AuditLoginService $auditService,
        protected readonly Request $request,
    ) {}

    public function handle(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;

        if (! $email) {
            return;
        }

        $cacheKey = sprintf(
            'audit_login_failed_%s_%s',
            md5($email),
            $this->request->ip() ?? 'unknown'
        );

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->addSeconds(5));

        $user = User::where('email', $email)->first();

        $this->auditService->createAudit(
            userId: $user?->id,
            ipAddress: $this->request->ip(),
            success: false,
            failureReason: $user ? 'invalid_password' : 'user_not_found',
            loginType: $this->detectLoginType($this->request),
            userAgent: $this->request->userAgent(),
        );
    }
}
