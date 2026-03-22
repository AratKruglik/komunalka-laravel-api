<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Address\Models\Address;
use Modules\Address\Models\UserAddress;
use Modules\Auth\Database\Factories\UserFactory;
use Modules\Auth\Enums\AuthProvider;
use Modules\Auth\Enums\UserRole;
use Modules\Auth\Policies\UserPolicy;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[UsePolicy(UserPolicy::class)]
class User extends Authenticatable implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;
    use Notifiable;

    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'phone_number',
        'email',
        'password',
        'role',
        'auth_provider',
        'external_id',
        'email_verified',
        'last_login_at',
        'name',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function casts(): array
    {
        return [
            'email_verified' => 'boolean',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'auth_provider' => AuthProvider::class,
        ];
    }

    /** @return BelongsToMany<Address, $this> */
    public function addresses(): BelongsToMany
    {
        return $this->belongsToMany(Address::class, 'address_user')
            ->using(UserAddress::class)
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    /** @return HasMany<RefreshToken, $this> */
    public function refreshTokens(): HasMany
    {
        return $this->hasMany(RefreshToken::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/gif', 'image/heic', 'image/heif']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('optimized')
            ->width(800)
            ->format('jpg')
            ->quality(85)
            ->queued();

        $this->addMediaConversion('thumbnail')
            ->width(200)
            ->format('jpg')
            ->quality(85)
            ->queued();
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
