<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class EnforceSingleSession
{
    /**
     * Cache key storing the active session ID for a given user.
     */
    private const CACHE_KEY = 'user_active_session_%d';

    /**
     * Cache key marking that a new login just occurred for a given user.
     * Set by the Login listener; consumed and deleted by this middleware.
     */
    private const PENDING_KEY = 'user_session_pending_%d';

    /**
     * Called by LogSuccessfulLogin to signal that a new login occurred.
     * Does NOT store the session ID — that happens here after regeneration.
     */
    public static function markNewLogin(int $userId): void
    {
        Cache::put(self::buildPendingKey($userId), true, now()->addSeconds(10));
    }

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $userId = Auth::id();
        $sessionId = $request->session()->getId();
        $activeKey = self::buildKey($userId);
        $pendingKey = self::buildPendingKey($userId);

        if (Cache::has($pendingKey)) {
            Cache::forget($pendingKey);
            Cache::forever($activeKey, $sessionId);

            return $next($request);
        }

        $activeSessionId = Cache::get($activeKey);

        if ($activeSessionId === null) {
            Cache::forever($activeKey, $sessionId);

            return $next($request);
        }

        if ($activeSessionId !== $sessionId) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return to_route('login')
                ->with('error', 'Sua sessão foi encerrada porque sua conta foi acessada em outro dispositivo.');
        }

        return $next($request);
    }

    public static function updateSession(int $userId, string $sessionId): void
    {
        Cache::forever(self::buildKey($userId), $sessionId);
    }

    private static function buildKey(int $userId): string
    {
        return sprintf(self::CACHE_KEY, $userId);
    }

    private static function buildPendingKey(int $userId): string
    {
        return sprintf(self::PENDING_KEY, $userId);
    }
}
