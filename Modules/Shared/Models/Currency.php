<?php

declare(strict_types=1);

namespace Modules\Shared\Models;

use Illuminate\Database\Eloquent\Attributes\UsePolicy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Database\Factories\CurrencyFactory;
use Modules\Shared\Policies\CurrencyPolicy;

#[UsePolicy(CurrencyPolicy::class)]
class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
    ];

    protected static function newFactory(): CurrencyFactory
    {
        return CurrencyFactory::new();
    }
}
