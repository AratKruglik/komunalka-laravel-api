<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Address\Database\Seeders\AddressDatabaseSeeder;
use Modules\Auth\Database\Seeders\AuthDatabaseSeeder;
use Modules\Shared\Database\Seeders\SharedDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            SharedDatabaseSeeder::class,
            AuthDatabaseSeeder::class,
            AddressDatabaseSeeder::class,
        ]);
    }
}
