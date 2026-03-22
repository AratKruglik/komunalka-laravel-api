<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Actions\GetOAuthUrl;
use Modules\Auth\Actions\HandleOAuthCallback;
use Modules\Auth\Actions\LinkOAuthProvider;
use Modules\Auth\Actions\LoginUser;
use Modules\Auth\Actions\OAuthLogin;
use Modules\Auth\Actions\RefreshUserToken;
use Modules\Auth\Actions\RegisterUser;
use Modules\Auth\Actions\RevokeToken;
use Modules\Auth\Actions\UnlinkOAuthProvider;
use Modules\Auth\Actions\ValidateToken;
use Modules\Auth\DTO\LoginData;
use Modules\Auth\DTO\OAuthCallbackData;
use Modules\Auth\DTO\OAuthLoginData;
use Modules\Auth\DTO\RefreshTokenData;
use Modules\Auth\DTO\RegisterUserData;
use Modules\Auth\Enums\AuthProvider;
use Modules\Auth\Http\Requests\LinkOAuthRequest;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Http\Requests\OAuthCallbackRequest;
use Modules\Auth\Http\Requests\OAuthLoginRequest;
use Modules\Auth\Http\Requests\RefreshTokenRequest;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Http\Requests\UnlinkOAuthRequest;
use Modules\Auth\Http\Resources\AuthenticationResource;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Models\User;
use Modules\Auth\Services\JwtService;

class AuthController extends Controller
{
    public function __construct(
        private JwtService $jwtService,
    ) {}

    public function register(RegisterRequest $request, RegisterUser $action): AuthenticationResource
    {
        $user = $action->handle(RegisterUserData::fromRequest($request));

        return new AuthenticationResource($this->jwtService->generateTokenPair($user));
    }

    public function login(LoginRequest $request, LoginUser $action): AuthenticationResource
    {
        $user = $action->handle(LoginData::fromRequest($request));

        return new AuthenticationResource($this->jwtService->generateTokenPair($user));
    }

    public function refreshToken(RefreshTokenRequest $request, RefreshUserToken $action): AuthenticationResource
    {
        $result = $action->handle(RefreshTokenData::fromRequest($request));

        return new AuthenticationResource($result);
    }

    public function revokeToken(RefreshTokenRequest $request, RevokeToken $action): JsonResponse
    {
        $action->handle(RefreshTokenData::fromRequest($request));

        return response()->json(['message' => 'Token revoked successfully.']);
    }

    public function validateToken(ValidateToken $action): UserResource
    {
        return new UserResource($action->handle());
    }

    public function oauthLogin(OAuthLoginRequest $request, OAuthLogin $action): AuthenticationResource
    {
        $user = $action->handle(OAuthLoginData::fromRequest($request));

        return new AuthenticationResource($this->jwtService->generateTokenPair($user));
    }

    public function oauthAuthorize(string $provider, GetOAuthUrl $action): JsonResponse
    {
        $result = $action->handle(AuthProvider::from($provider));

        return response()->json($result);
    }

    public function oauthCallback(OAuthCallbackRequest $request, HandleOAuthCallback $action): AuthenticationResource
    {
        $user = $action->handle(OAuthCallbackData::fromRequest($request));

        return new AuthenticationResource($this->jwtService->generateTokenPair($user));
    }

    public function linkOAuth(LinkOAuthRequest $request, LinkOAuthProvider $action): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        $action->handle(
            $user,
            AuthProvider::from($request->validated('provider')),
            $request->validated('token'),
        );

        return response()->json(['message' => 'OAuth provider linked successfully.']);
    }

    public function unlinkOAuth(string $provider, UnlinkOAuthRequest $request, UnlinkOAuthProvider $action): JsonResponse
    {
        /** @var User $user */
        $user = auth('api')->user();

        $action->handle($user, $request->validated('password'));

        return response()->json(['message' => 'OAuth provider unlinked successfully.']);
    }
}
