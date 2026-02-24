<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\UsersController;

Route::prefix('v1/users')->name('users.')->group(function (): void {
    Route::get('{id}/avatar', [UsersController::class, 'avatar'])->name('avatar');
    Route::get('{id}/avatar/thumbnail', [UsersController::class, 'avatarThumbnail'])->name('avatar.thumbnail');

    Route::middleware('auth:api')->group(function (): void {
        Route::get('/', [UsersController::class, 'index'])->name('index');
        Route::get('{id}', [UsersController::class, 'show'])->name('show');
        Route::post('/', [UsersController::class, 'store'])->name('store');
        Route::put('{id}', [UsersController::class, 'update'])->name('update');
        Route::delete('{id}', [UsersController::class, 'destroy'])->name('destroy');
    });
});

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
