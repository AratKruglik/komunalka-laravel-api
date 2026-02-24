<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

use Modules\Auth\Http\Requests\UpdateUserRequest;

final readonly class UpdateUserData
{
    public function __construct(
        public ?string $firstName,
        public ?string $lastName,
        public ?string $phoneNumber,
        public ?string $currentPassword,
        public ?string $newPassword,
    ) {}

    public static function fromRequest(UpdateUserRequest $request): self
    {
        return new self(
            firstName: $request->validated('first_name'),
            lastName: $request->validated('last_name'),
            phoneNumber: $request->validated('phone_number'),
            currentPassword: $request->validated('current_password'),
            newPassword: $request->validated('new_password'),
        );
    }
}
