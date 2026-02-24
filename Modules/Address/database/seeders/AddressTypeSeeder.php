<?php

declare(strict_types=1);

namespace Modules\Address\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Address\Models\AddressType;

class AddressTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Квартира', 'description' => 'Квартира в багатоповерховому будинку', 'icon' => 'apartment'],
            ['name' => 'Приватний будинок', 'description' => 'Приватний житловий будинок', 'icon' => 'home'],
            ['name' => 'Офіс', 'description' => 'Офісне приміщення', 'icon' => 'office'],
        ];

        foreach ($types as $type) {
            AddressType::query()->firstOrCreate(
                ['name' => $type['name']],
                $type,
            );
        }
    }
}
