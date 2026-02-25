<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
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
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UsersController extends Controller
{
    public function index(GetAllUsers $action): AnonymousResourceCollection
    {
        return UserResource::collection($action->handle());
    }

    public function show(int $id, GetUser $action): UserResource
    {
        return new UserResource($action->handle($id));
    }

    public function store(StoreUserRequest $request, CreateUser $action): UserResource
    {
        $user = $action->handle(CreateUserData::fromRequest($request));

        return new UserResource($user);
    }

    public function update(int $id, UpdateUserRequest $request, GetUser $getUser, UpdateUser $action): UserResource
    {
        $user = $getUser->handle($id);
        $updated = $action->handle($user, UpdateUserData::fromRequest($request), $request->file('avatar'));

        return new UserResource($updated);
    }

    public function destroy(int $id, GetUser $getUser, DeleteUser $action): JsonResponse
    {
        $user = $getUser->handle($id);
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
