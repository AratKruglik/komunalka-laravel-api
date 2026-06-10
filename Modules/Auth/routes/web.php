<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Auth\Actions\DestroyUserAccount;
use Modules\Auth\Actions\HandleOAuthCallback;
use Modules\Auth\Actions\LoginUser;
use Modules\Auth\Actions\LogoutUser;
use Modules\Auth\Actions\Pages\ForgotPasswordPage;
use Modules\Auth\Actions\Pages\LoginPage;
use Modules\Auth\Actions\Pages\RegisterPage;
use Modules\Auth\Actions\Pages\ResetPasswordPage;
use Modules\Auth\Actions\Pages\SettingsPage;
use Modules\Auth\Actions\RedirectToOAuthProvider;
use Modules\Auth\Actions\RegisterUser;
use Modules\Auth\Actions\ResetUserPassword;
use Modules\Auth\Actions\SendPasswordResetLink;
use Modules\Auth\Actions\UnlinkOAuthProvider;
use Modules\Auth\Actions\UpdateUser;
use Modules\Auth\Actions\UpdateUserPassword;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', LoginPage::class)->name('login');
    Route::post('/login', LoginUser::class);
    Route::get('/register', RegisterPage::class)->name('register');
    Route::post('/register', RegisterUser::class);
    Route::get('/auth/{provider}/redirect', RedirectToOAuthProvider::class)->name('oauth.redirect');
    Route::get('/auth/{provider}/callback', HandleOAuthCallback::class)->name('oauth.callback');

    Route::get('/forgot-password', ForgotPasswordPage::class)->name('password.request');
    Route::post('/forgot-password', SendPasswordResetLink::class)->name('password.email');
    Route::get('/reset-password/{token}', ResetPasswordPage::class)->name('password.reset');
    Route::post('/reset-password', ResetUserPassword::class)->name('password.update');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', LogoutUser::class)->name('logout');

    Route::get('settings', SettingsPage::class)->name('settings');
    Route::put('settings/profile', UpdateUser::class)->name('settings.profile');
    Route::put('settings/password', UpdateUserPassword::class)->name('settings.password');
    Route::delete('settings/account', DestroyUserAccount::class)->name('settings.account');
    Route::get('settings/oauth/{provider}/link', RedirectToOAuthProvider::class)->name('settings.oauth.link');
    Route::delete('settings/oauth/{provider}', UnlinkOAuthProvider::class)->name('settings.oauth.unlink');
});
