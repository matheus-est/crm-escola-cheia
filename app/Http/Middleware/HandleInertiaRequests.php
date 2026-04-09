<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\SchoolStatus;
use App\Models\School;
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
                'user' => $user ? [
                    'uuid' => $user->uuid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role ? ['name' => $user->role->name] : null,
                ] : null,
                'permissions' => $user?->role?->permissions->pluck('name') ?? collect(),
                'is_cross_tenant' => $user?->isCrossTenant() ?? false,

                'schools' => function () use ($request) {
                    $user = $request->user();
                    if ($user === null) {
                        return [];
                    }
                    if ($user->isCrossTenant()) {
                        return School::query()
                            ->where('status', SchoolStatus::Active->value)
                            ->get(['uuid', 'legal_name', 'trade_name']);
                    }

                    return $user->schools()
                        ->wherePivot('is_active', true)
                        ->get(['schools.uuid', 'schools.legal_name', 'schools.trade_name']);
                },

                'current_school' => function () use ($request) {
                    $user = $request->user();
                    if ($user === null) {
                        return null;
                    }

                    if (app()->bound('tenant.school_id')) {
                        $school = School::find(app('tenant.school_id'));
                        if ($school) {
                            return ['uuid' => $school->uuid, 'legal_name' => $school->legal_name, 'trade_name' => $school->trade_name];
                        }
                    }

                    $sessionUuid = session('active_school_uuid');
                    if ($sessionUuid) {
                        $school = School::query()
                            ->where('uuid', $sessionUuid)
                            ->where('status', SchoolStatus::Active)
                            ->first();
                        if ($school) {
                            return ['uuid' => $school->uuid, 'legal_name' => $school->legal_name, 'trade_name' => $school->trade_name];
                        }
                    }

                    if ($user->school_current_id !== null) {
                        $school = School::find($user->school_current_id);
                        if ($school && $school->status === SchoolStatus::Active) {
                            return ['uuid' => $school->uuid, 'legal_name' => $school->legal_name, 'trade_name' => $school->trade_name];
                        }
                    }

                    return null;
                },
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
