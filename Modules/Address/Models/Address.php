<?php

declare(strict_types=1);

namespace Modules\Address\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Address\Database\Factories\AddressFactory;
use Modules\Auth\Models\User;

class Address extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'region_id',
        'address_type_id',
        'city',
        'street',
        'building_number',
        'apartment_number',
        'zip_code',
        'notes',
    ];

    /** @return BelongsTo<Region, $this> */
    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    /** @return BelongsTo<AddressType, $this> */
    public function addressType(): BelongsTo
    {
        return $this->belongsTo(AddressType::class);
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'address_user')
            ->using(UserAddress::class)
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    protected static function newFactory(): AddressFactory
    {
        return AddressFactory::new();
    }
}
