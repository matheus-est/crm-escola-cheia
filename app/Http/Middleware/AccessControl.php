<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Acl\UserService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccessControl
{
    public function __construct(
        protected readonly UserService $userService,
    ) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user || ! $this->userService->hasPermission($user, $permission)) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized');
        }

        return $next($request);
    }
}
