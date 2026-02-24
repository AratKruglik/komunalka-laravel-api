<?php

declare(strict_types=1);

namespace Modules\Auth\DTOs;

use Modules\Auth\Http\Requests\RegisterRequest;

final readonly class RegisterUserData
{
    public function __construct(
        public string $username,
        public string $firstName,
        public string $lastName,
        public string $phoneNumber,
        public string $email,
        public string $password,
    ) {}

    public static function fromRequest(RegisterRequest $request): self
    {
        return new self(
            username: $request->validated('username'),
            firstName: $request->validated('first_name'),
            lastName: $request->validated('last_name'),
            phoneNumber: $request->validated('phone_number'),
            email: $request->validated('email'),
            password: $request->validated('password'),
        );
    }
}
