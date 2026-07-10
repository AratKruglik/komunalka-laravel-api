<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Resources;

use App\Http\Resources\InertiaJsonApiResource;
use Illuminate\Http\Request;
use Modules\Address\Http\Resources\AddressResource;
use Modules\Auth\Models\User;
use Modules\Shared\Concerns\ResolvesMediaConversionUrls;

/** @mixin User */
class UserResource extends InertiaJsonApiResource
{
    use ResolvesMediaConversionUrls;

    /** @return array<string, mixed> */
    public function toAttributes(Request $request): array
    {
        return [
            'username' => $this->username,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'role' => $this->role?->value,
            'auth_provider' => $this->auth_provider?->value,
            'email_verified' => $this->email_verified,
            'last_login_at' => $this->last_login_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'avatar' => $this->when(
                $this->relationLoaded('media'),
                fn () => $this->resolveMediaConversionUrls($this->getFirstMedia('avatar')),
            ),
        ];
    }

    /** @return array<string, string> */
    public function toRelationships(Request $request): array
    {
        return [
            'addresses' => AddressResource::class,
        ];
    }
}
