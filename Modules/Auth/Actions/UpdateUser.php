<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\DTO\UpdateUserData;
use Modules\Auth\Http\Requests\UpdateUserRequest;
use Modules\Auth\Models\User;
use Modules\Auth\Repositories\Contracts\UserRepositoryInterface;

class UpdateUser
{
    use AsAction;

    public function __construct(private UserRepositoryInterface $userRepository) {}

    public function handle(User $user, UpdateUserData $data, ?UploadedFile $avatar): User
    {
        $attributes = array_filter([
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'phone_number' => $data->phoneNumber,
        ], fn (mixed $value): bool => $value !== null);

        if ($data->firstName !== null || $data->lastName !== null) {
            $attributes['name'] = trim(($data->firstName ?? $user->first_name).' '.($data->lastName ?? $user->last_name));
        }

        if ($data->newPassword !== null) {
            $attributes['password'] = Hash::make($data->newPassword);
        }

        if ($attributes !== []) {
            /** @var User $user */
            $user = $this->userRepository->update($user, $attributes);
        }

        if ($avatar !== null) {
            $user->addMedia($avatar)->toMediaCollection('avatar');
        }

        return $user->load(['addresses.region', 'addresses.addressType', 'media']);
    }

    public function asController(UpdateUserRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $this->handle(
            $user,
            UpdateUserData::fromRequest($request),
            $request->file('avatar'),
        );

        return back()->with('success', 'Профіль успішно оновлено.');
    }
}
