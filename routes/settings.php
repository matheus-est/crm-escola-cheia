<?php

use App\Http\Controllers\Settings\LoginHistoryController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SystemSettingController;
use App\Http\Controllers\Settings\TwoFactorAuthenticationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('settings/profile/export', [ProfileController::class, 'export'])->name('profile.export');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('user-password.edit');

    Route::put('settings/password', [PasswordController::class, 'update'])
        ->middleware('throttle:6,1')
        ->name('user-password.update');

    Route::get('settings/two-factor', [TwoFactorAuthenticationController::class, 'show'])
        ->name('two-factor.show');

    Route::match(['get', 'post'], 'settings/login-history', [LoginHistoryController::class, 'show'])
        ->name('login-history.edit');
});

Route::middleware(['auth', 'can:settings_manage'])->group(function () {
    Route::get('settings/system', [SystemSettingController::class, 'index'])->name('settings.system.index');
    Route::put('settings/system/identity', [SystemSettingController::class, 'updateIdentity'])->name('settings.system.identity.update');
    Route::put('settings/system/security', [SystemSettingController::class, 'updateSecurity'])->name('settings.system.security.update');
    Route::put('settings/system/email', [SystemSettingController::class, 'updateEmail'])->name('settings.system.email.update');
    Route::put('settings/system/lgpd', [SystemSettingController::class, 'updateLgpd'])->name('settings.system.lgpd.update');
});
