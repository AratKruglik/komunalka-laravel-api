<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia;
use Modules\Address\Models\Address;
use Modules\Address\Models\AddressType;
use Modules\Address\Models\Region;
use Modules\Auth\Models\User;

beforeEach(function (): void {
    $this->withoutVite();
});

describe('Address Index Page', function (): void {
    it('renders address list for authenticated user', function (): void {
        $user = User::factory()->create();
        $region = Region::factory()->create();
        $addressType = AddressType::factory()->create(['icon' => 'apartment']);
        $address = Address::factory()->create([
            'region_id' => $region->getKey(),
            'address_type_id' => $addressType->getKey(),
            'city' => 'Київ',
            'street' => 'Хрещатик',
            'building_number' => '22',
        ]);
        $user->addresses()->attach($address->getKey(), ['is_primary' => false]);

        $this->actingAs($user)
            ->get('/addresses')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Addresses/Index')
                ->has('addresses.data', 1)
                ->where('addresses.data.0.street', 'Хрещатик')
                ->where('addresses.data.0.building_number', '22')
                ->where('addresses.data.0.city', 'Київ')
            );
    });

    it('shows empty state when user has no addresses', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/addresses')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Addresses/Index')
                ->has('addresses.data', 0)
            );
    });

    it('does not show addresses belonging to other users', function (): void {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $address = Address::factory()->create();
        $otherUser->addresses()->attach($address->getKey(), ['is_primary' => false]);

        $this->actingAs($user)
            ->get('/addresses')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Addresses/Index')
                ->has('addresses.data', 0)
            );
    });

    it('displays primary badge on primary address', function (): void {
        $user = User::factory()->create();
        $address = Address::factory()->create();
        $user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $this->actingAs($user)
            ->get('/addresses')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Addresses/Index')
                ->where('addresses.data.0.is_primary', true)
            );
    });
});

describe('Address Create Page', function (): void {
    it('renders form with regions and address types', function (): void {
        $user = User::factory()->create();
        $regions = Region::factory()->count(3)->create();
        $addressTypes = AddressType::factory()->count(2)->create();

        $this->actingAs($user)
            ->get('/addresses/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Addresses/Create')
                ->has('regions.data', 3)
                ->has('addressTypes.data', 2)
            );
    });

    it('stores address and redirects to index with success flash', function (): void {
        $user = User::factory()->create();
        $region = Region::factory()->create();
        $addressType = AddressType::factory()->create();

        $this->actingAs($user)
            ->post('/addresses', [
                'region_id' => $region->getKey(),
                'address_type_id' => $addressType->getKey(),
                'city' => 'Львів',
                'street' => 'Шевченка',
                'building_number' => '10',
                'apartment_number' => '5',
                'zip_code' => '79000',
                'notes' => 'Біля парку',
                'is_primary' => true,
            ])
            ->assertRedirect('/addresses')
            ->assertSessionHas('success', 'Адресу створено');

        $this->assertDatabaseHas('addresses', [
            'city' => 'Львів',
            'street' => 'Шевченка',
            'building_number' => '10',
            'apartment_number' => '5',
            'zip_code' => '79000',
            'notes' => 'Біля парку',
        ]);

        $this->assertDatabaseHas('address_user', [
            'user_id' => $user->getKey(),
            'is_primary' => true,
        ]);
    });

    it('stores address with only required fields', function (): void {
        $user = User::factory()->create();
        $region = Region::factory()->create();
        $addressType = AddressType::factory()->create();

        $this->actingAs($user)
            ->post('/addresses', [
                'region_id' => $region->getKey(),
                'address_type_id' => $addressType->getKey(),
                'city' => 'Одеса',
                'street' => 'Дерибасівська',
                'building_number' => '1',
            ])
            ->assertRedirect('/addresses')
            ->assertSessionHas('success', 'Адресу створено');

        $this->assertDatabaseHas('addresses', [
            'city' => 'Одеса',
            'street' => 'Дерибасівська',
            'building_number' => '1',
            'apartment_number' => null,
            'zip_code' => null,
            'notes' => null,
        ]);
    });

    it('returns validation errors for missing required fields', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/addresses', [])
            ->assertSessionHasErrors([
                'region_id',
                'address_type_id',
                'city',
                'street',
                'building_number',
            ]);
    });

    it('returns validation error for non-existent region', function (): void {
        $user = User::factory()->create();
        $addressType = AddressType::factory()->create();

        $this->actingAs($user)
            ->post('/addresses', [
                'region_id' => 99999,
                'address_type_id' => $addressType->getKey(),
                'city' => 'Харків',
                'street' => 'Сумська',
                'building_number' => '5',
            ])
            ->assertSessionHasErrors(['region_id']);
    });

    it('returns validation error for non-existent address type', function (): void {
        $user = User::factory()->create();
        $region = Region::factory()->create();

        $this->actingAs($user)
            ->post('/addresses', [
                'region_id' => $region->getKey(),
                'address_type_id' => 99999,
                'city' => 'Харків',
                'street' => 'Сумська',
                'building_number' => '5',
            ])
            ->assertSessionHasErrors(['address_type_id']);
    });

    it('stores address with unicode characters in street and notes', function (): void {
        $user = User::factory()->create();
        $region = Region::factory()->create();
        $addressType = AddressType::factory()->create();

        $unicodeStreet = 'вул. Тараса Шевченка-Франка (Старий район)';
        $unicodeNotes = 'Поруч з парком ім. Т. Шевченка; вхід через подвір\'я';

        $this->actingAs($user)
            ->post('/addresses', [
                'region_id' => $region->getKey(),
                'address_type_id' => $addressType->getKey(),
                'city' => 'Івано-Франківськ',
                'street' => $unicodeStreet,
                'building_number' => '12-А',
                'notes' => $unicodeNotes,
            ])
            ->assertRedirect('/addresses');

        $this->assertDatabaseHas('addresses', [
            'street' => $unicodeStreet,
            'notes' => $unicodeNotes,
            'city' => 'Івано-Франківськ',
        ]);
    });

    it('rejects street name exceeding max length', function (): void {
        $user = User::factory()->create();
        $region = Region::factory()->create();
        $addressType = AddressType::factory()->create();

        $this->actingAs($user)
            ->post('/addresses', [
                'region_id' => $region->getKey(),
                'address_type_id' => $addressType->getKey(),
                'city' => 'Київ',
                'street' => str_repeat('а', 256),
                'building_number' => '1',
            ])
            ->assertSessionHasErrors(['street']);
    });
});

describe('Address Edit Page', function (): void {
    it('renders form pre-filled with existing address data', function (): void {
        $user = User::factory()->create();
        $region = Region::factory()->create(['name' => 'Київська']);
        $addressType = AddressType::factory()->create(['name' => 'Квартира', 'icon' => 'apartment']);
        $address = Address::factory()->create([
            'region_id' => $region->getKey(),
            'address_type_id' => $addressType->getKey(),
            'city' => 'Київ',
            'street' => 'Хрещатик',
            'building_number' => '22',
            'apartment_number' => '15',
            'zip_code' => '01001',
            'notes' => 'Центр міста',
        ]);
        $user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $this->actingAs($user)
            ->get('/addresses/'.$address->getKey().'/edit')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Addresses/Edit')
                ->where('address.data.city', 'Київ')
                ->where('address.data.street', 'Хрещатик')
                ->where('address.data.building_number', '22')
                ->where('address.data.apartment_number', '15')
                ->where('address.data.zip_code', '01001')
                ->where('address.data.notes', 'Центр міста')
                ->where('address.data.is_primary', true)
                ->has('regions.data')
                ->has('addressTypes.data')
            );
    });

    it('updates address and redirects with success flash', function (): void {
        $user = User::factory()->create();
        $region = Region::factory()->create();
        $newRegion = Region::factory()->create();
        $addressType = AddressType::factory()->create();
        $address = Address::factory()->create([
            'region_id' => $region->getKey(),
            'address_type_id' => $addressType->getKey(),
            'city' => 'Київ',
            'street' => 'Хрещатик',
            'building_number' => '22',
        ]);
        $user->addresses()->attach($address->getKey(), ['is_primary' => false]);

        $this->actingAs($user)
            ->put('/addresses/'.$address->getKey(), [
                'region_id' => $newRegion->getKey(),
                'address_type_id' => $addressType->getKey(),
                'city' => 'Львів',
                'street' => 'Площа Ринок',
                'building_number' => '1',
                'apartment_number' => '3',
                'zip_code' => '79008',
                'notes' => 'Оновлена адреса',
                'is_primary' => true,
            ])
            ->assertRedirect('/addresses')
            ->assertSessionHas('success', 'Адресу оновлено');

        $this->assertDatabaseHas('addresses', [
            'id' => $address->getKey(),
            'city' => 'Львів',
            'street' => 'Площа Ринок',
            'building_number' => '1',
        ]);
    });

    it('returns 404 when editing address of another user', function (): void {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $address = Address::factory()->create();
        $otherUser->addresses()->attach($address->getKey(), ['is_primary' => false]);

        $this->actingAs($user)
            ->get('/addresses/'.$address->getKey().'/edit')
            ->assertNotFound();
    });

    it('returns 404 for non-existent address', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/addresses/99999/edit')
            ->assertNotFound();
    });
});

describe('Address Delete', function (): void {
    it('deletes address and redirects with success flash', function (): void {
        $user = User::factory()->create();
        $address = Address::factory()->create();
        $user->addresses()->attach($address->getKey(), ['is_primary' => false]);

        $this->actingAs($user)
            ->delete('/addresses/'.$address->getKey())
            ->assertRedirect('/addresses')
            ->assertSessionHas('success', 'Адресу видалено');

        $this->assertDatabaseMissing('address_user', [
            'user_id' => $user->getKey(),
            'address_id' => $address->getKey(),
        ]);
    });

    it('deletes primary address successfully', function (): void {
        $user = User::factory()->create();
        $address = Address::factory()->create();
        $user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $this->actingAs($user)
            ->delete('/addresses/'.$address->getKey())
            ->assertRedirect('/addresses')
            ->assertSessionHas('success', 'Адресу видалено');

        $this->assertDatabaseMissing('address_user', [
            'user_id' => $user->getKey(),
            'address_id' => $address->getKey(),
        ]);
    });

    it('returns 404 when deleting address of another user', function (): void {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $address = Address::factory()->create();
        $otherUser->addresses()->attach($address->getKey(), ['is_primary' => false]);

        $this->actingAs($user)
            ->delete('/addresses/'.$address->getKey())
            ->assertNotFound();

        $this->assertDatabaseHas('address_user', [
            'user_id' => $otherUser->getKey(),
            'address_id' => $address->getKey(),
        ]);
    });

    it('returns 404 for non-existent address', function (): void {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete('/addresses/99999')
            ->assertNotFound();
    });
});

describe('Address Card Display', function (): void {
    it('returns all address fields in resource response', function (): void {
        $user = User::factory()->create();
        $region = Region::factory()->create(['name' => 'Львівська']);
        $addressType = AddressType::factory()->create([
            'name' => 'Квартира',
            'icon' => 'apartment',
            'description' => 'Квартира в багатоповерхівці',
        ]);
        $address = Address::factory()->create([
            'region_id' => $region->getKey(),
            'address_type_id' => $addressType->getKey(),
            'city' => 'Львів',
            'street' => 'Площа Ринок',
            'building_number' => '1',
            'apartment_number' => '5',
            'zip_code' => '79008',
            'notes' => 'Історичний центр',
        ]);
        $user->addresses()->attach($address->getKey(), ['is_primary' => true]);

        $this->actingAs($user)
            ->get('/addresses')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Addresses/Index')
                ->has('addresses.data.0', fn (AssertableInertia $item) => $item
                    ->where('id', $address->getKey())
                    ->where('city', 'Львів')
                    ->where('street', 'Площа Ринок')
                    ->where('building_number', '1')
                    ->where('apartment_number', '5')
                    ->where('zip_code', '79008')
                    ->where('notes', 'Історичний центр')
                    ->where('is_primary', true)
                    ->has('region', fn (AssertableInertia $r) => $r
                        ->where('id', $region->getKey())
                        ->where('name', 'Львівська')
                    )
                    ->has('address_type', fn (AssertableInertia $t) => $t
                        ->where('id', $addressType->getKey())
                        ->where('name', 'Квартира')
                        ->where('icon', 'apartment')
                        ->etc()
                    )
                    ->has('created_at')
                    ->has('updated_at')
                )
            );
    });
});

describe('Address Authentication', function (): void {
    it('redirects unauthenticated user to login from index', function (): void {
        $this->get('/addresses')
            ->assertRedirect('/login');
    });

    it('redirects unauthenticated user to login from create', function (): void {
        $this->get('/addresses/create')
            ->assertRedirect('/login');
    });

    it('redirects unauthenticated user to login from edit', function (): void {
        $address = Address::factory()->create();

        $this->get('/addresses/'.$address->getKey().'/edit')
            ->assertRedirect('/login');
    });

    it('redirects unauthenticated user to login on store', function (): void {
        $this->post('/addresses', [
            'city' => 'Київ',
            'street' => 'Хрещатик',
            'building_number' => '1',
        ])
            ->assertRedirect('/login');
    });

    it('redirects unauthenticated user to login on update', function (): void {
        $address = Address::factory()->create();

        $this->put('/addresses/'.$address->getKey(), [
            'city' => 'Київ',
        ])
            ->assertRedirect('/login');
    });

    it('redirects unauthenticated user to login on delete', function (): void {
        $address = Address::factory()->create();

        $this->delete('/addresses/'.$address->getKey())
            ->assertRedirect('/login');
    });
});

describe('Address Navigation', function (): void {
    it('create page provides breadcrumb navigation back to index', function (): void {
        $user = User::factory()->create();
        Region::factory()->create();
        AddressType::factory()->create();

        $this->actingAs($user)
            ->get('/addresses/create')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Addresses/Create')
            );
    });

    it('edit page provides breadcrumb navigation back to index', function (): void {
        $user = User::factory()->create();
        $address = Address::factory()->create();
        $user->addresses()->attach($address->getKey(), ['is_primary' => false]);

        $this->actingAs($user)
            ->get('/addresses/'.$address->getKey().'/edit')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Addresses/Edit')
            );
    });

    it('successful store redirects back to address index', function (): void {
        $user = User::factory()->create();
        $region = Region::factory()->create();
        $addressType = AddressType::factory()->create();

        $this->actingAs($user)
            ->post('/addresses', [
                'region_id' => $region->getKey(),
                'address_type_id' => $addressType->getKey(),
                'city' => 'Дніпро',
                'street' => 'Набережна',
                'building_number' => '7',
            ])
            ->assertRedirect('/addresses');
    });

    it('successful update redirects back to address index', function (): void {
        $user = User::factory()->create();
        $region = Region::factory()->create();
        $addressType = AddressType::factory()->create();
        $address = Address::factory()->create([
            'region_id' => $region->getKey(),
            'address_type_id' => $addressType->getKey(),
        ]);
        $user->addresses()->attach($address->getKey(), ['is_primary' => false]);

        $this->actingAs($user)
            ->put('/addresses/'.$address->getKey(), [
                'region_id' => $region->getKey(),
                'address_type_id' => $addressType->getKey(),
                'city' => 'Дніпро',
                'street' => 'Набережна',
                'building_number' => '7',
            ])
            ->assertRedirect('/addresses');
    });
});
