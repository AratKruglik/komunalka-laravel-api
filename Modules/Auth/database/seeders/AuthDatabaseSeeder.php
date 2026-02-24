<?php

declare(strict_types=1);

namespace Modules\Auth\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;

class AuthDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@komunalka.com',
            'name' => 'Admin',
        ]);

        User::factory()->create([
            'email' => 'user@komunalka.com',
            'name' => 'User',
        ]);
    }
}
