<?php

declare(strict_types=1);

namespace App\Providers;

use App\Listeners\LogFailedLogin;
use App\Listeners\LogLogout;
use App\Listeners\LogSuccessfulLogin;
use App\Models\School;
use App\Services\SettingService;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->configureDefaults();
        $this->registerEventListeners();
        $this->registerRouteBindings();

        try {
            $settingService = app(SettingService::class);

            $fromAddress = $settingService->get('mail_from_address');
            $fromName = $settingService->get('mail_from_name');

            if ($fromAddress) {
                config(['mail.from.address' => $fromAddress]);
            }

            if ($fromName) {
                config(['mail.from.name' => $fromName]);
            }
        } catch (\Throwable) {
            // Silently fail during migrations, first boot or if table doesn't exist yet
        }
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    protected function registerEventListeners(): void
    {
        Event::listen(Login::class, LogSuccessfulLogin::class);
        Event::listen(Failed::class, LogFailedLogin::class);
        Event::listen(Logout::class, LogLogout::class);
    }

    protected function registerRouteBindings(): void
    {
        Route::bind('school_uuid', static function (string $value): School {
            return School::query()->where('uuid', $value)->firstOrFail();
        });
    }
}
