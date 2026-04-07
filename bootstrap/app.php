<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnforceSingleSession;
use App\Http\Middleware\EnsureTenantAccess;
use App\Http\Middleware\ForcePasswordChange;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetActiveTenant;
use App\Http\Middleware\TermAcceptanceRequired;
use App\Services\SettingService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));

            Route::middleware('web')
                ->group(base_path('routes/tenant.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->alias([
            'role' => CheckRole::class,
            'term.acceptance' => TermAcceptanceRequired::class,
            'tenant' => SetActiveTenant::class,
            'tenant.access' => EnsureTenantAccess::class,
        ]);

        $middleware->web(append: [
            HandleAppearance::class,
            SetActiveTenant::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            ForcePasswordChange::class,
            EnforceSingleSession::class,
            TermAcceptanceRequired::class,
        ]);

        $middleware->priority([
            ForcePasswordChange::class,
            TermAcceptanceRequired::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('terms:activate-scheduled')
            ->dailyAt('00:00')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/terms-scheduler.log'));

        $schedule->call(function (): void {
            $days = (int) app(SettingService::class)->get('audit_retention_days', 180);
            Artisan::call("audit:clean --days={$days}");
        })
            ->name('audit.clean.dynamic')
            ->dailyAt('02:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/audit-clean.log'));

        $schedule->command('audit-logs:purge')
            ->dailyAt('02:30')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/audit-login-purge.log'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
