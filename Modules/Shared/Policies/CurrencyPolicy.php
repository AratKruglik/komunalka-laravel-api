<?php

declare(strict_types=1);

namespace Modules\Shared\Policies;

use Modules\Auth\Models\User;
use Modules\Shared\Models\Currency;

class CurrencyPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Currency $currency): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Currency $currency): bool
    {
        return false;
    }

    public function delete(User $user, Currency $currency): bool
    {
        return false;
    }
}
