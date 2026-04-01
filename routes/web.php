<?php

use App\Http\Controllers\Settings\PasswordChangeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::post('/locale/{locale}', function ($locale) {
    if (! in_array($locale, config('app.locales', ['pt_BR', 'en', 'es']))) {
        abort(400);
    }
    session(['locale' => $locale]);

    return back();
});

Route::get('/', function () {
    if (auth()->check()) {
        return to_route('dashboard');
    }

    return Inertia::render('auth/Login', [
        'canResetPassword' => Features::enabled(Features::resetPasswords()),
        'canRegister' => Features::enabled(Features::registration()),
        'status' => session('status'),
    ]);
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('password/change', [PasswordChangeController::class, 'show'])->name('password.change');
    Route::post('password/change', [PasswordChangeController::class, 'update'])->name('password.change.update');
});

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/acl.php';
