<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Auth\AuthenticationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\Models\User;

class ValidateToken
{
    use AsAction;

    public function handle(): User
    {
        $user = auth('api')->user();

        if (! $user instanceof User) {
            throw new AuthenticationException;
        }

        return $user;
    }
}
