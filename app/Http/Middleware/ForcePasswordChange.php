<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Acl\UserService;
use App\Services\SettingService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    public function __construct(
        protected UserService $userService,
        protected SettingService $settingService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $this->userService->mustChangePassword($user)) {
            $allowedRoutes = [
                'password.change',
                'password.change.update',
                'logout',
                'profile.edit',
            ];

            $currentRoute = $request->route()?->getName();

            if (! in_array($currentRoute, $allowedRoutes)) {
                return to_route('password.change');
            }
        }

        return $next($request);
    }
}
