<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Address\Http\Resources\AddressResource;
use Modules\Auth\Models\User;

/** @mixin User */
class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
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
            'addresses' => AddressResource::collection($this->whenLoaded('addresses')),
            'avatar_optimized_url' => $this->when(
                $this->relationLoaded('media'),
                fn () => $this->getFirstMediaUrl('avatar', 'optimized') ?: null,
            ),
            'avatar_thumbnail_url' => $this->when(
                $this->relationLoaded('media'),
                fn () => $this->getFirstMediaUrl('avatar', 'thumbnail') ?: null,
            ),
        ];
    }
}
