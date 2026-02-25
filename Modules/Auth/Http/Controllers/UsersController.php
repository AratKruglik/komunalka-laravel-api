<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Modules\Auth\Actions\CreateUser;
use Modules\Auth\Actions\DeleteUser;
use Modules\Auth\Actions\GetAllUsers;
use Modules\Auth\Actions\GetUser;
use Modules\Auth\Actions\GetUserAvatar;
use Modules\Auth\Actions\UpdateUser;
use Modules\Auth\DTO\CreateUserData;
use Modules\Auth\DTO\UpdateUserData;
use Modules\Auth\Http\Requests\StoreUserRequest;
use Modules\Auth\Http\Requests\UpdateUserRequest;
use Modules\Auth\Http\Resources\UserResource;
use Modules\Auth\Models\User;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UsersController extends Controller
{
    public function index(GetAllUsers $action): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', User::class);

        return UserResource::collection($action->handle());
    }

    public function show(int $id, GetUser $action): UserResource
    {
        $user = $action->handle($id);
        Gate::authorize('view', $user);

        return new UserResource($user);
    }

    public function store(StoreUserRequest $request, CreateUser $action): UserResource
    {
        Gate::authorize('create', User::class);
        $user = $action->handle(CreateUserData::fromRequest($request));

        return new UserResource($user);
    }

    public function update(int $id, UpdateUserRequest $request, GetUser $getUser, UpdateUser $action): UserResource
    {
        $user = $getUser->handle($id);
        Gate::authorize('update', $user);
        $updated = $action->handle($user, UpdateUserData::fromRequest($request), $request->file('avatar'));

        return new UserResource($updated);
    }

    public function destroy(int $id, GetUser $getUser, DeleteUser $action): JsonResponse
    {
        $user = $getUser->handle($id);
        Gate::authorize('delete', $user);
        $action->handle($user);

        return response()->json(['message' => 'User deleted successfully.']);
    }

    public function avatar(int $id, GetUser $getUser, GetUserAvatar $action): BinaryFileResponse
    {
        $user = $getUser->handle($id);

        return $action->handle($user, 'optimized');
    }

    public function avatarThumbnail(int $id, GetUser $getUser, GetUserAvatar $action): BinaryFileResponse
    {
        $user = $getUser->handle($id);

        return $action->handle($user, 'thumbnail');
    }
}
