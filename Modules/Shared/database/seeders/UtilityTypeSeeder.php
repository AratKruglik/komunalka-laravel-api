<?php

declare(strict_types=1);

namespace Modules\Shared\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Shared\Models\UtilityType;

class UtilityTypeSeeder extends Seeder
{
    public function run(): void
    {
        UtilityType::query()->upsert([
            ['slug' => 'electricity', 'display_name' => 'Електроенергія', 'unit' => 'kWh'],
            ['slug' => 'gas', 'display_name' => 'Газ', 'unit' => 'm³'],
            ['slug' => 'cold-water', 'display_name' => 'Холодна вода', 'unit' => 'm³'],
            ['slug' => 'hot-water', 'display_name' => 'Гаряча вода', 'unit' => 'm³'],
            ['slug' => 'heating', 'display_name' => 'Опалення', 'unit' => 'Gcal'],
            ['slug' => 'sewage', 'display_name' => 'Каналізація', 'unit' => 'm³'],
        ], ['slug'], ['display_name', 'unit']);
    }
}
