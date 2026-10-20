<?php

declare(strict_types=1);

namespace Modules\Ai\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Modules\Address\Models\AddressType;
use Modules\Address\Models\Region;
use Modules\Auth\Models\User;
use Modules\Billing\Models\ServiceProvider;
use Modules\Meter\Models\Meter;
use Modules\Shared\Models\Currency;
use Modules\Shared\Models\UtilityType;
use Stringable;

class ListPropertySetupContext implements Tool
{
    public function __construct(private readonly User $user) {}

    public function name(): string
    {
        return 'list_property_setup_context';
    }

    public function description(): Stringable|string
    {
        return 'Показати доступні довідникові значення та власні адреси, провайдерів і лічильники для підготовки створення.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function handle(Request $request): Stringable|string
    {
        $addressIds = $this->user->addresses()->pluck('addresses.id')->all();

        return json_encode([
            'regions' => Region::query()->orderBy('name')->limit(100)->get(['id', 'name'])->toArray(),
            'address_types' => AddressType::query()->orderBy('name')->limit(30)->get(['id', 'name'])->toArray(),
            'utility_types' => UtilityType::query()->active()->orderBy('display_name')->limit(30)->get(['id', 'slug', 'display_name'])->toArray(),
            'currencies' => Currency::query()->orderBy('code')->limit(20)->get(['id', 'code'])->toArray(),
            'addresses' => $this->user->addresses()->limit(50)->get(['addresses.id', 'city', 'street', 'building_number'])->toArray(),
            'providers' => ServiceProvider::query()->whereIn('address_id', $addressIds)->limit(50)->get(['id', 'address_id', 'utility_type_id', 'name'])->toArray(),
            'meters' => Meter::query()->whereIn('address_id', $addressIds)->limit(50)->get(['id', 'address_id', 'utility_type_id', 'serial_number', 'name'])->toArray(),
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}
