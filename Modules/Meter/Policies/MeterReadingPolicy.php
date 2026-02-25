<?php

declare(strict_types=1);

namespace Modules\Meter\Policies;

use Modules\Address\Models\UserAddress;
use Modules\Auth\Models\User;
use Modules\Meter\Models\MeterReading;

class MeterReadingPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function view(User $user, MeterReading $reading): bool
    {
        return UserAddress::query()
            ->where('user_id', $user->id)
            ->where('address_id', $reading->meter->address_id)
            ->exists();
    }

    public function delete(User $user, MeterReading $reading): bool
    {
        return UserAddress::query()
            ->where('user_id', $user->id)
            ->where('address_id', $reading->meter->address_id)
            ->exists();
    }
}
