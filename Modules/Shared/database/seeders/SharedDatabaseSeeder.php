<?php

declare(strict_types=1);

namespace Modules\Shared\Database\Seeders;

use Illuminate\Database\Seeder;

class SharedDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UtilityTypeSeeder::class,
            CurrencySeeder::class,
        ]);
    }
}
