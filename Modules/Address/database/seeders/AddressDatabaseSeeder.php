<?php

declare(strict_types=1);

namespace Modules\Address\Database\Seeders;

use Illuminate\Database\Seeder;

class AddressDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RegionSeeder::class,
            AddressTypeSeeder::class,
        ]);
    }
}
