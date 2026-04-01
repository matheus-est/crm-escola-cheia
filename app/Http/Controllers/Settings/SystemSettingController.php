<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\EmailSettingsRequest;
use App\Http\Requests\Settings\IdentitySettingsRequest;
use App\Http\Requests\Settings\LgpdsSettingsRequest;
use App\Http\Requests\Settings\SecuritySettingsRequest;
use App\Services\SettingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingController extends Controller
{
    public function __construct(
        protected readonly SettingService $settingService,
    ) {}

    public function index(): Response
    {
        return Inertia::render('settings/SystemSetting', [
            'identity' => $this->settingService->getGroup('identity'),
            'security' => $this->settingService->getGroup('security'),
            'email' => $this->settingService->getGroup('email'),
            'lgpd' => $this->settingService->getGroup('lgpd'),
        ]);
    }

    public function updateIdentity(IdentitySettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $currentIdentity = $this->settingService->getGroup('identity');

        foreach (['logo_light', 'logo_dark', 'favicon'] as $field) {
            if ($request->hasFile($field)) {
                $validated[$field] = $this->settingService->uploadImage($field, $request->file($field));
            } elseif (array_key_exists($field, $currentIdentity)) {
                $validated[$field] = $currentIdentity[$field];
            }
        }

        $this->settingService->setGroup('identity', $validated);

        return back()->with('success', 'Identity settings updated successfully.');
    }

    /**
     * Update security settings.
     */
    public function updateSecurity(SecuritySettingsRequest $request): RedirectResponse
    {
        $this->settingService->setGroup('security', $request->validated());

        return back()->with('success', 'Security settings updated successfully.');
    }

    /**
     * Update email settings.
     */
    public function updateEmail(EmailSettingsRequest $request): RedirectResponse
    {
        $this->settingService->setGroup('email', $request->validated());

        return back()->with('success', 'Email settings updated successfully.');
    }

    /**
     * Update LGPD settings.
     */
    public function updateLgpd(LgpdsSettingsRequest $request): RedirectResponse
    {
        $this->settingService->setGroup('lgpd', $request->validated());

        return back()->with('success', 'LGPD settings updated successfully.');
    }
}
