<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Acl\TermService;
use App\Services\Menu\MenuService;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function __construct(
        protected readonly SettingService $settingService,
    ) {}

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $locale = app()->getLocale();
        $termService = app(TermService::class);
        $user = $request->user();

        if ($user) {
            $user->loadMissing(['role', 'role.permissions']);
        }

        $activeTerm = $user ? $termService->getActiveTerm() : null;
        $acceptance = $user ? $termService->getUserAcceptance($user) : null;

        return [
            ...parent::share($request),

            'locale' => fn () => $locale,
            'appearance' => fn () => $request->cookie('appearance') ?? 'system',

            'translations' => fn () => cache()->remember(
                "translations-{$locale}",
                3600,
                fn () => json_decode(file_get_contents(lang_path("{$locale}.json")), true)
            ),

            'name' => fn () => $this->settingService->get('app_name', config('app.name')),
            'logo_light' => fn () => $this->settingService->get('logo_light'),
            'logo_dark' => fn () => $this->settingService->get('logo_dark'),
            'company_name' => fn () => $this->settingService->get('company_name'),
            'dpo_email' => fn () => $this->settingService->get('dpo_email'),

            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'uuid' => $request->user()->uuid,
                    'name' => $request->user()->name,
                    'email' => $request->user()->email,
                    'role' => $request->user()->role ? [
                        'id' => $request->user()->role->id,
                        'name' => $request->user()->role->name,
                    ] : null,
                ] : null,
                'permissions' => $user?->role?->permissions->pluck('name') ?? collect(),
            ],

            'menu' => $this->getMenu($request),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',

            'currentTerm' => $activeTerm?->only(['id', 'uuid', 'version', 'title', 'content', 'effective_at', 'is_active']),
            'userAcceptance' => $acceptance?->only(['id', 'term_version_id', 'accepted_at']),
            'needsTermAcceptance' => $user ? $termService->needsAcceptance($user) : false,
        ];
    }

    protected function getMenu(Request $request): array
    {
        $user = $request->user();

        if (! $user) {
            return [];
        }

        $user->loadMissing(['role', 'role.permissions']);

        return app(MenuService::class)->getMenuForUser($request->user())->toArray();
    }
}
