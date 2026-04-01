<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Acl\UserService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AclServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->registerGates();
    }

    protected function registerGates(): void
    {
        Gate::before(function ($user, string $ability) {
            /** @var UserService $userService */
            $userService = app(UserService::class);

            if ($userService->hasPermission($user, $ability)) {
                return true;
            }

            return null;
        });

        Gate::define('access-module', function ($user, string $module, string $action) {
            /** @var UserService $userService */
            $userService = app(UserService::class);

            return $userService->canAccess($user, $module, $action);
        });
    }
}
