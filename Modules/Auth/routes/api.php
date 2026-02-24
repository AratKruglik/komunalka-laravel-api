<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;

Route::prefix('v1/auth')->name('auth.')->group(function (): void {
    Route::post('register', [AuthController::class, 'register'])->name('register');
    Route::post('login', [AuthController::class, 'login'])->name('login');
    Route::post('refresh-token', [AuthController::class, 'refreshToken'])->name('refresh-token');

    Route::post('oauth/login', [AuthController::class, 'oauthLogin'])->name('oauth.login');
    Route::get('oauth/{provider}/authorize', [AuthController::class, 'oauthAuthorize'])->name('oauth.authorize');
    Route::post('oauth/callback', [AuthController::class, 'oauthCallback'])->name('oauth.callback');

    Route::middleware('auth:api')->group(function (): void {
        Route::post('revoke-token', [AuthController::class, 'revokeToken'])->name('revoke-token');
        Route::get('validate-token', [AuthController::class, 'validateToken'])->name('validate-token');
        Route::post('oauth/link', [AuthController::class, 'linkOAuth'])->name('oauth.link');
        Route::delete('oauth/unlink/{provider}', [AuthController::class, 'unlinkOAuth'])->name('oauth.unlink');
    });
});
