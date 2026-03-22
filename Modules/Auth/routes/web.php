<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\Web\LoginController;
use Modules\Auth\Http\Controllers\Web\LogoutController;
use Modules\Auth\Http\Controllers\Web\OAuthController;
use Modules\Auth\Http\Controllers\Web\RegisterController;
use Modules\Auth\Http\Controllers\Web\SettingsController;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
    Route::get('/auth/{provider}/redirect', [OAuthController::class, 'redirect'])->name('oauth.redirect');
    Route::get('/auth/{provider}/callback', [OAuthController::class, 'callback'])->name('oauth.callback');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LogoutController::class, 'destroy'])->name('logout');

    Route::get('settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::put('settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::delete('settings/account', [SettingsController::class, 'destroyAccount'])->name('settings.account');
    Route::get('settings/oauth/{provider}/link', [SettingsController::class, 'linkOAuth'])->name('settings.oauth.link');
    Route::delete('settings/oauth/{provider}', [SettingsController::class, 'unlinkOAuth'])->name('settings.oauth.unlink');
});
