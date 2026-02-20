<?php

declare(strict_types=1);

namespace Modules\Shared\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Shared\Models\Currency;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        Currency::query()->firstOrCreate(
            ['code' => 'UAH'],
            ['name' => 'Українська гривня', 'symbol' => '₴'],
        );
    }
}
