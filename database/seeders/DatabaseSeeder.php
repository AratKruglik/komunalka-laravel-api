<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            \Modules\Shared\Database\Seeders\SharedDatabaseSeeder::class,
            \Modules\Auth\Database\Seeders\AuthDatabaseSeeder::class,
            \Modules\Address\Database\Seeders\AddressDatabaseSeeder::class,
        ]);
    }
}
