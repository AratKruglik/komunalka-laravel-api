# План реалізації: Міграція Komunalka API (.NET → Laravel)

## Огляд

Міграція розбита на 8 фаз, кожна з яких є самодостатнім milestone. Фази виконуються послідовно, кожна будує на результатах попередньої.

---

## ~~Фаза 0: Підготовка інфраструктури~~ DONE

### 0.1 Встановлення пакетів

```bash
docker compose exec app composer require nwidart/laravel-modules
docker compose exec app composer require lorisleiva/laravel-actions
docker compose exec app composer require php-open-source-saver/jwt-auth
docker compose exec app composer require laravel/socialite
docker compose exec app composer require spatie/laravel-medialibrary
docker compose exec app composer require league/csv
docker compose exec app composer require spatie/laravel-pdf
```

### 0.2 Конфігурація nwidart/laravel-modules

- [x] Опублікувати конфігурацію: `vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"`
- [x] Оновити `composer.json` — додати `"Modules\\": "Modules/"` до PSR-4 autoload
- [x] Оновити `phpunit.xml` — додати `Modules/*/tests/Feature` та `Modules/*/tests/Unit`
- [x] Налаштувати generator paths в `config/modules.php` для DDD структури

### 0.2a Конфігурація spatie/laravel-medialibrary

- [x] Опублікувати міграцію: `vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"`
- [x] Опублікувати конфігурацію: `vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-config"`
- [x] Налаштувати disk, max_file_size, queue в `config/media-library.php`
- [x] Запустити міграцію для створення `media` таблиці

### 0.2b Base Repository та DTO інфраструктура

- [x] Створити `app/Repositories/Contracts/RepositoryInterface.php` — base interface (all, find, findOrFail, create, update, delete, paginate)
- [x] Створити `app/Repositories/EloquentRepository.php` — base Eloquent implementation
- [ ] Документація: конвенція іменування DTO (`*Data.php`), Repository (`*Repository.php`, `*RepositoryInterface.php`)

### 0.3 Конфігурація JWT

- [x] Опублікувати конфігурацію: `vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"`
- [x] Згенерувати секрет: `php artisan jwt:secret`
- [x] Налаштувати claims, TTL, refresh TTL в `config/jwt.php`

### 0.4 Конфігурація Socialite

- [x] Додати Google та GitHub credentials до `.env`
- [x] Налаштувати providers в `config/services.php`

### 0.5 Docker — imagick extension (для spatie/laravel-medialibrary HEIC підтримки)

- [x] Додати `php-imagick` та `libheif` до Dockerfile
- [x] Перебілдити контейнери

### 0.6 Структура каталогу docs/

- [x] PRD.md
- [x] ADR.md
- [x] IMPLEMENTATION_PLAN.md

**Deliverable:** ~~Всі пакети встановлені, конфігурації опубліковані, Docker оновлений.~~ DONE

---

## ~~Фаза 1: Shared Module — базові entities та seeders~~ DONE

### 1.1 Створення модуля

```bash
docker compose exec app php artisan module:make Shared
```

### 1.2 Моделі

- [x] `UtilityType` — id, slug, display_name, unit, description, is_active, created_at, updated_at
  - Зв'язки: hasMany(Meter), hasMany(Tariff), hasMany(ServiceProvider)
  - Scope: `active()`
- [x] `Currency` — id, code, name, symbol, created_at, updated_at
  - Зв'язки: hasMany(Tariff)
- [x] `ServiceCategory` — id, name, created_at, updated_at
  - Зв'язки: belongsToMany(Address)
- [x] `ServiceCounter` — id, address_id, service_category_id, serial_number, service_counter_measurement_id, created_at, updated_at
- [x] `ServiceCounterMeasurement` — id, name, measurement, created_at, updated_at
- [x] `ServiceCounterValue` — id, service_counter_id, value (float), created_at, updated_at
- [x] `AddressesServiceCategory` (pivot model)

### 1.3 Міграції

- [x] `create_utility_types_table` — з seed data (6 типів)
- [x] `create_currencies_table`
- [x] `create_service_categories_table`
- [x] `create_service_counter_measurements_table`
- [x] `create_service_counters_table`
- [x] `create_service_counter_values_table`
- [x] `create_address_service_category_table`

### 1.4 Seeders

- [x] `UtilityTypeSeeder` — electricity, gas, cold-water, hot-water, heating, sewage
- [x] `CurrencySeeder` — UAH (₴) як мінімум

### 1.5 Factories

- [x] Factories для кожної моделі

### 1.5a Repositories

- [x] `CurrencyRepositoryInterface` — all, find, create, update, delete
- [x] `CurrencyRepository` (extends EloquentRepository)
- [x] `UtilityTypeRepositoryInterface` — all, findBySlug, getActive
- [x] `UtilityTypeRepository` (extends EloquentRepository)
- [x] Bind interfaces в `SharedServiceProvider`

### 1.5b DTOs

- [x] `CreateCurrencyData` — code, name, symbol (readonly, fromRequest)
- [x] `UpdateCurrencyData` — code, name, symbol (readonly, fromRequest)

### 1.6 Actions (CRUD для Currency)

- [x] `GetAllCurrencies` — список всіх валют
- [x] `GetCurrency` — отримання за ID
- [x] `CreateCurrency` — створення
- [x] `UpdateCurrency` — оновлення
- [x] `DeleteCurrency` — видалення

### 1.7 Actions (Read-only для UtilityType)

- [x] `GetActiveUtilityTypes` — список активних
- [x] `GetUtilityType` — отримання за ID

### 1.8 HTTP Layer

- [x] `CurrencyController` — CRUD (public, no auth)
- [x] `UtilityTypeController` — GET only (public)
- [x] Form Requests: `StoreCurrencyRequest`, `UpdateCurrencyRequest`
- [x] API Resources: `CurrencyResource`, `UtilityTypeResource`
- [x] Routes: `api/v1/currencies/*`, `api/v1/utility-types/*`

### 1.9 Tests

- [x] Feature tests: Currency CRUD endpoints
- [x] Feature tests: UtilityType GET endpoints
- [x] Unit tests: Actions

**Deliverable:** ~~Currency та UtilityType ендпоінти працюють, seed data доступна.~~ DONE

---

## ~~Фаза 2: Auth Module — автентифікація~~ DONE

### 2.1 Створення модуля

```bash
docker compose exec app php artisan module:make Auth
```

### 2.2 Моделі

- [x] `User` — всі поля з PRD, implements JWTSubject
  - Зв'язки: hasMany(UserAddress), hasMany(RefreshToken)
  - Unique: email, compound (auth_provider, external_id)
  - Casts: email_verified (boolean), last_login_at (datetime)
  - Hidden: password, remember_token
- [x] `RefreshToken` — token, expiry_date, is_used, is_revoked, user_id
  - Зв'язки: belongsTo(User)

### 2.3 Міграції

- [x] `add_auth_columns_to_users_table` — всі поля включаючи OAuth fields
- [x] `create_refresh_tokens_table`

### 2.4 Seeders та Factories

- [x] `UserFactory` — з states: admin, oauthGoogle, oauthGithub, unverified
- [x] `RefreshTokenFactory`

### 2.4a Repositories

- [x] `UserRepositoryInterface` — findByEmail, findByExternalId(provider, externalId), create, update, delete
- [x] `UserRepository` (extends EloquentRepository)
- [x] `RefreshTokenRepositoryInterface` — findValidByToken, create, revokeAllForUser, deleteExpired
- [x] `RefreshTokenRepository` (extends EloquentRepository)
- [x] Bind interfaces в `AuthServiceProvider`

### 2.4b DTOs

- [x] `RegisterUserData` — username, firstName, lastName, phoneNumber, email, password (readonly, fromRequest)
- [x] `LoginData` — email, password (readonly, fromRequest)
- [x] `RefreshTokenData` — refreshToken (readonly, fromRequest)
- [x] `OAuthLoginData` — provider, token (readonly, fromRequest)
- [x] `OAuthCallbackData` — provider, code, state, error, errorDescription (readonly, fromRequest)
- [ ] `UpdateUserData` — username, firstName, lastName, phoneNumber, email, currentPassword, newPassword (readonly, fromRequest) — перенесено до Фази 6

### 2.5 Services

- [x] `JwtService` — generateTokenPair, refreshTokenPair, getTokenExpiration
  - Custom claims: sub, email, name, role, jti
  - Конфігурація: JWT_EXPIRATION_MINUTES (30), JWT_REFRESH_TOKEN_EXPIRATION_DAYS (7)
- [x] `OAuthService` — getAuthorizationUrl, handleCallback, authenticateWithToken, findOrCreateUser, generateUniqueUsername
  - Інтеграція з Socialite
  - CSRF state через Cache (5 хв)

### 2.6 Actions

- [x] `RegisterUser` — валідація, BCrypt hash, JWT generation
- [x] `LoginUser` — email/password auth, prevent OAuth-only users
- [x] `RefreshUserToken` — validate used/revoked/expired, issue new pair
- [x] `RevokeToken` — mark as revoked
- [x] `ValidateToken` — check JWT validity
- [x] `OAuthLogin` — authenticate via provider token
- [x] `GetOAuthUrl` — generate authorization URL
- [x] `HandleOAuthCallback` — process auth code callback
- [x] `LinkOAuthProvider` — link provider to existing user
- [x] `UnlinkOAuthProvider` — unlink provider (requires password)

### 2.7 HTTP Layer

- [x] `AuthController` — route-to-action mapping
- [x] Form Requests: `LoginRequest`, `RegisterRequest`, `RefreshTokenRequest`, `OAuthLoginRequest`, `OAuthCallbackRequest`, `LinkOAuthRequest`, `UnlinkOAuthRequest`
- [x] API Resources: `AuthenticationResource`, `UserResource`
- [x] Routes: `api/v1/auth/*`
- [x] Middleware: JWT auth guard registration

### 2.8 Tests

- [x] Feature: register, login, refresh, revoke, validate
- [x] Feature: OAuth login, callback, link, unlink
- [x] Unit: JwtService token generation/validation
- [x] Unit: RegisterUser action, LoginUser action

**Deliverable:** ~~Повний auth flow працює — register, login, JWT, refresh, OAuth.~~ DONE

---

## ~~Фаза 3: Address Module — адреси та регіони~~ DONE

### 3.1 Створення модуля

```bash
docker compose exec app php artisan module:make Address
```

### 3.2 Моделі

- [x] `Address` — всі поля, soft deletes
  - Зв'язки: belongsTo(Region), belongsTo(AddressType), belongsToMany(User via UserAddress), hasMany(Meter), hasMany(ServiceProvider)
- [x] `AddressType` — name, description, icon
  - Зв'язки: hasMany(Address)
- [x] `Region` — name
  - Зв'язки: hasMany(Address)
- [x] `UserAddress` (pivot model, table: address_user) — user_id, address_id, is_primary
  - Unique composite: (user_id, address_id)
  - Unique filtered: тільки один is_primary=true per user

### 3.3 Міграції

- [x] `create_regions_table`
- [x] `create_address_types_table`
- [x] `create_addresses_table` — з soft deletes
- [x] `create_address_user_table` — з unique constraints
- [x] `add_foreign_key_to_address_service_category_table` — deferred FK від Shared module

### 3.4 Seeders

- [x] `RegionSeeder` — 27 регіонів (25 областей + АР Крим + м. Київ + м. Севастополь)
- [x] `AddressTypeSeeder` — Квартира, Приватний будинок, Офіс

### 3.5 Factories

- [x] `AddressFactory`, `AddressTypeFactory`, `RegionFactory`

### 3.5a Repositories

- [x] `AddressRepositoryInterface` — getForUser(userId, perPage, sortBy, desc), findForUser(userId, addressId)
- [x] `AddressRepository` (extends EloquentRepository)
- [x] `AddressTypeRepositoryInterface` — all, find
- [x] `AddressTypeRepository`
- [x] `RegionRepositoryInterface` — all, find
- [x] `RegionRepository`
- [x] `UserAddressRepositoryInterface` — attach, detach, setPrimary, clearPrimary, userOwnsAddress
- [x] `UserAddressRepository`
- [x] Bind interfaces в `AddressServiceProvider`

### 3.5b DTOs

- [x] `CreateAddressData` — regionId, addressTypeId, city, street, buildingNumber, apartmentNumber, zipCode, notes, isPrimary (readonly, fromRequest)
- [x] `UpdateAddressData` — regionId, addressTypeId, city, street, buildingNumber, apartmentNumber, zipCode, notes, isPrimary (readonly, fromRequest)
- [x] `PatchAddressData` — all nullable for partial updates (readonly, fromRequest)
- [x] `AddressPaginationData` — page, perPage, sortBy, desc (readonly, fromRequest/query)

### 3.6 Actions

- [x] `GetUserAddresses` — пагінація, сортування (city, updatedAt, isPrimary, createdAt)
- [x] `GetUserAddress` — з перевіркою доступу через UserAddress
- [x] `CreateAddress` — створення + UserAddress pivot + primary address logic
- [x] `UpdateAddress` — оновлення + primary address enforcement
- [x] `PatchAddress` — partial update + toggle primary
- [x] `DeleteAddress` — soft delete + pivot cleanup
- [x] `GetAllAddressTypes` — список типів
- [x] `GetAddressType` — тип за ID
- [x] `GetAllRegions` — список регіонів
- [x] `GetRegion` — регіон за ID

### 3.7 HTTP Layer

- [x] `AddressController` — CRUD з пагінацією (index, show, store, update, patch, destroy)
- [x] `AddressTypeController` — GET only
- [x] `RegionController` — GET only
- [x] Form Requests: `StoreAddressRequest`, `UpdateAddressRequest`, `PatchAddressRequest` (з українськими повідомленнями)
- [x] API Resources: `AddressResource` (з nested Region, AddressType), `AddressTypeResource`, `RegionResource`
- [x] Routes: `api/v1/address/*`, `api/v1/addresstype/*`, `api/v1/region/*` (10 endpoints, auth:api)

### 3.8 Tests

- [x] Feature: Address CRUD з пагінацією та сортуванням
- [x] Feature: Multi-tenancy (user A не бачить адреси user B)
- [x] Feature: Primary address logic
- [x] Feature: AddressType та Region endpoints
- [x] Unit: CreateAddress, UpdateAddress, PatchAddress, DeleteAddress actions

**Deliverable:** ~~Повне управління адресами, регіонами, типами адрес з multi-tenancy.~~ DONE

---

## ~~Фаза 4: Billing Module — постачальники та тарифи~~ DONE

### 4.1 Створення модуля

```bash
docker compose exec app php artisan module:make Billing
```

### 4.2 Моделі

- [x] `ServiceProvider` — name, description, phone, email, website, address_id, utility_type_id, is_active
  - Зв'язки: belongsTo(Address), belongsTo(UtilityType), hasMany(Meter), hasMany(Tariff)
  - Cascade delete від Address, Restrict від UtilityType
- [x] `Tariff` — service_provider_id, utility_type_id, currency_id, name, base_rate, service_fee, effective_from, effective_to, notes
  - Зв'язки: belongsTo(ServiceProvider), belongsTo(UtilityType), belongsTo(Currency)
  - Scope: `effectiveAt(DateTime $date)` — фільтр за EffectiveFrom/To

### 4.3 Міграції

- [x] `create_service_providers_table`
- [x] `create_tariffs_table`

### 4.4 Factories

- [x] `ServiceProviderFactory`, `TariffFactory`

### 4.4a Repositories

- [x] `ServiceProviderRepositoryInterface` — getByAddressIds, getByAddressId, findWithTariffs, create, update, delete
- [x] `ServiceProviderRepository`
- [x] `TariffRepositoryInterface` — getEffective(serviceProviderId, date), getEffectiveForUtilityType, create, update
- [x] `TariffRepository`
- [x] Bind interfaces в `BillingServiceProvider`

### 4.4b DTOs

- [x] `CreateServiceProviderData` — addressId, utilityTypeId, name, description, phone, email, website, isActive, tariffs[] (readonly, fromRequest)
- [x] `UpdateServiceProviderData` — name, description, phone, email, website, isActive, utilityTypeId (readonly, fromRequest)
- [x] `CreateTariffData` — utilityTypeId, currencyId, name, baseRate, serviceFee, effectiveFrom, effectiveTo, notes (readonly)
- [x] `TariffCalculationResult` — meterId, meterName, consumption, unit, baseRate, serviceFee, totalCost, currencyCode, currencySymbol (readonly)

### 4.5 Actions

- [x] `GetUserServiceProviders` — постачальники для адрес поточного користувача
- [x] `GetServiceProvider` — за ID з тарифами
- [x] `GetServiceProvidersByAddress` — за addressId
- [x] `CreateServiceProvider` — створення з вкладеними тарифами
- [x] `UpdateServiceProvider` — оновлення
- [x] `DeleteServiceProvider` — видалення
- [x] `GetEffectiveTariff` — пошук діючого тарифу за serviceProviderId + utilityTypeId + date
- [x] `CalculateTariffCost` — розрахунок: (consumption × BaseRate) + ServiceFee

### 4.6 HTTP Layer

- [x] `ServiceProviderController` — CRUD
- [x] Form Requests: `StoreServiceProviderRequest`, `UpdateServiceProviderRequest`
- [x] API Resources: `ServiceProviderResource`, `TariffResource`
- [x] Routes: `api/v1/service-providers/*`

### 4.7 Tests

- [x] Feature: ServiceProvider CRUD
- [x] Feature: Authorization (user owns address)
- [x] Unit: CalculateTariffCost з різними сценаріями
- [x] Unit: GetEffectiveTariff — date range logic

**Deliverable:** ~~Повне управління постачальниками та тарифами з розрахунками.~~ DONE

---

## Фаза 5: Meter Module — лічильники та показники

### 5.1 Створення модуля

```bash
docker compose exec app php artisan module:make Meter
```

### 5.2 Моделі

- [x] `Meter` — address_id, utility_type_id, serial_number, name, description, model_name, location, installation_date, initial_reading, service_provider_id, notes, is_active
  - Зв'язки: belongsTo(Address), belongsTo(UtilityType), belongsTo(ServiceProvider nullable), hasMany(MeterReading)
  - Scope: `active()`, `forAddress(int $addressId)`
  - **Implements HasMedia** — media collection `photo` (singleFile) для фото лічильника
- [x] `MeterReading` — meter_id, reading_value, reading_date, previous_reading_value, consumption, notes, is_estimated, tariff_id
  - Зв'язки: belongsTo(Meter), belongsTo(Tariff nullable)
  - **Implements HasMedia** — media collection `photos` (multiple) для фото показників
  - Conversions: `optimized` (800px, JPEG 85%), `thumbnail` (200px, JPEG 85%) — queued
- [x] ~~MeterReadingPhoto~~ — **ЗАМІНЕНО** на spatie/laravel-medialibrary `media` таблицю
- [x] ~~MeterReadingImage~~ — **ЗАМІНЕНО** на spatie/laravel-medialibrary `media` таблицю

### 5.3 Міграції

- [x] `create_meters_table` — FK: address_id, utility_type_id, service_provider_id (nullable, onDelete SET NULL)
- [x] `create_meter_readings_table` — FK: meter_id, tariff_id (nullable)
- [x] ~~create_meter_reading_photos_table~~ — НЕ потрібна (spatie media table)
- [x] ~~create_meter_reading_images_table~~ — НЕ потрібна (spatie media table)

### 5.4 Factories

- [x] `MeterFactory`, `MeterReadingFactory`

### 5.4a Repositories

- [x] `MeterRepositoryInterface` — getByAddressId, getActive, findWithRelations, create, update, delete
- [x] `MeterRepository`
- [x] `MeterReadingRepositoryInterface` — getByAddressId(from, to), getByMeterId, findWithPhotos, create, delete
- [x] `MeterReadingRepository`
- [x] Bind interfaces в `MeterServiceProvider`

### 5.4b DTOs

- [x] `CreateMeterData` — addressId, utilityTypeId, serialNumber, name, description, modelName, location, installationDate, initialReading, serviceProviderId, notes, isActive (readonly, fromRequest)
- [x] `UpdateMeterData` — serialNumber, name, description, modelName, location, installationDate, initialReading, serviceProviderId, notes, isActive (all nullable, readonly, fromRequest)
- [x] `CreateMeterReadingData` — meterId, readingValue, readingDate, notes, isEstimated, tariffId (readonly)
- [x] `BatchReadingData` — addressId, readings: CreateMeterReadingData[] (readonly, fromRequest)
- [ ] `ExportRequestData` — addressIds[], fromDate, toDate, format (readonly, fromRequest)

### 5.5 Media (spatie/laravel-medialibrary)

Замість custom ImageService/FileStorageService/Jobs:
- [x] Визначити media collections на `MeterReading` model: `photos` (multiple, mime: jpeg/png/webp, max 10MB)
- [x] Визначити media collections на `Meter` model: `photo` (singleFile, mime: jpeg/png/webp)
- [x] Визначити conversions: `optimized` (800px, quality 85, jpg, queued) та `thumbnail` (200px, quality 85, jpg, queued)
- [x] Налаштувати queue connection для media conversions в `config/media-library.php`

### 5.7 Actions

#### Meter CRUD
- [x] `GetAllMeters` — всі лічильники
- [x] `GetMeter` — за ID
- [x] `GetMetersByAddress` — за addressId
- [x] `GetActiveMeters` — тільки активні
- [x] `CreateMeter` — створення (JSON body)
- [x] `UploadMeterPhoto` — завантаження фото лічильника (через medialibrary: `$meter->addMedia()->toMediaCollection('photo')`)
- [x] `UpdateMeter` — оновлення (partial update)
- [x] `DeleteMeter` — видалення

#### Meter Reading
- [x] `CreateBatchReadings` — пакетне створення з фото та розрахунками тарифів
  - Валідація: meter belongs to address, is active, reading >= previous
  - Авто-визначення тарифу
  - Розрахунок consumption та cost
  - Фото: `$reading->addMedia($file)->toMediaCollection('photos')` — conversions queued автоматично
- [x] `GetReadingsByAddress` — за addressId з date range filter
- [x] `GetMeterReading` — за ID
- [x] `DeleteMeterReading` — видалення + cleanup файлів

#### Legacy MeterReading (ServiceCounterValue)
- [x] `CreateServiceCounterValue` — створення показника (legacy)
- [x] `GetServiceCounterValue` — отримання за ID
- [x] `DeleteServiceCounterValue` — видалення

### 5.8 HTTP Layer

- [x] `MeterController` — CRUD
- [x] `BatchMeterReadingsController` — batch create, get by address, photos serving
- [x] `LegacyMeterReadingController` — legacy endpoints
- [x] `MeterReadingPhotoController` — public photo serving
- [x] Form Requests: `StoreMeterRequest`, `UpdateMeterRequest`, `UploadMeterPhotoRequest`, `StoreMeterReadingRequest`, `BatchMeterReadingRequest`
- [x] API Resources: `MeterResource`, `MeterReadingResource` (з media URLs через `getFirstMediaUrl`), `BatchMeterReadingResponse`, `TariffCalculationResource`
- [x] Routes: `api/v1/meter/*`, `api/v1/meter-readings/*`, `api/v1/meterreading/*`
- [x] Photo serving endpoints: використовують `$reading->getFirstMediaUrl('photos', 'optimized')` та `thumbnail`

### 5.9 Tests

- [x] Feature: Meter CRUD
- [x] Feature: Batch readings creation з тарифними розрахунками
- [x] Feature: Photo upload та serving
- [x] Feature: Multi-tenancy validation
- [x] Feature: Reading value validation (>= previous)
- [x] Unit: CreateBatchReadings action
- [x] Unit: CreateMeter action
- [x] Unit: DeleteMeter action
- [x] Feature: Legacy MeterReading (ServiceCounterValue) endpoints

**Deliverable:** Повне управління лічильниками, показниками, фото з тарифними розрахунками.

---

## Фаза 6: Users Module та Export Module

### 6.1 Users (розширення Auth Module)

**Media (spatie/laravel-medialibrary):**
- [ ] Визначити media collection на `User` model: `avatar` (singleFile, mime: jpeg/png/gif/heic/heif, max 2MB)
- [ ] Conversions: `optimized` (800px, quality 85, jpg, queued), `thumbnail` (200px, quality 85, jpg, queued)
- [ ] singleFile — автоматично видаляє попередній аватар при завантаженні нового

**Repositories:**
- [ ] Розширити `UserRepositoryInterface` — getAllWithAddresses, findWithAddresses

**DTOs:**
- [ ] `UpdateUserData` вже створено у Фазі 2

**Actions:**
- [ ] `GetAllUsers` — список всіх
- [ ] `GetUser` — за ID з адресами
- [ ] `CreateUser` — створення (admin)
- [ ] `UpdateUser` — оновлення профілю + avatar через medialibrary (`$user->addMedia()->toMediaCollection('avatar')`) + password change
- [ ] `DeleteUser` — видалення (medialibrary автоматично cleanup файли)
- [ ] `GetUserAvatar` — отримання аватару (`$user->getFirstMediaUrl('avatar', 'optimized')`)

**HTTP Layer:**
- [ ] `UsersController` — CRUD + avatar endpoints
- [ ] Form Requests: `UpdateUserRequest` — username, profile fields, password change, avatar (multipart)
- [ ] API Resources: `UserResource` (з nested AddressResource, avatar URLs через medialibrary)
- [ ] Routes: `api/v1/users/*`
- [ ] Avatar routes — public (no auth), redirect до media URL

### 6.2 Export Module

```bash
docker compose exec app php artisan module:make Export
```

**Actions:**
- [ ] `ExportMeterReadings` — orchestrator: fetch data → generate file
- [ ] `ExportToCsv` — RFC 4180 CSV generation
- [ ] `ExportToPdf` — A4 landscape PDF generation

**HTTP Layer:**
- [ ] `ExportController`
- [ ] Form Requests: `ExportMeterReadingsRequest` — address_ids[], from_date, to_date, format (csv/pdf)
- [ ] Routes: `api/v1/export/meter-readings`

### 6.3 Tests

- [ ] Feature: User CRUD з avatar
- [ ] Feature: Password change (requires current password)
- [ ] Feature: Export CSV/PDF з date filtering
- [ ] Unit: ExportToCsv, ExportToPdf actions

**Deliverable:** User management та export functionality.

---

## Фаза 7: Cross-cutting concerns

### 7.1 Global Exception Handler

- [ ] Створити custom exception handler в `bootstrap/app.php`
- [ ] Маппінг exceptions → HTTP responses (аналогічно .NET ExceptionHandlerMiddleware):
  - ModelNotFoundException → 404
  - AuthenticationException → 401
  - AuthorizationException → 403
  - ValidationException → 422
  - QueryException → 409 (для constraint violations)
  - Default → 500
- [ ] Структурований JSON формат (statusCode, message, details, timestamp, path)
- [ ] Сховати details у production

### 7.2 API Response Format

- [ ] Створити `ApiResponse` wrapper trait або macro
- [ ] Пагінація через Laravel built-in (вже Laravel-compatible формат)
- [ ] Забезпечити однаковий формат links/meta

### 7.3 Rate Limiting

- [ ] Налаштувати throttle middleware в `bootstrap/app.php`
- [ ] Rate limits для auth endpoints (строгіші)
- [ ] Rate limits для загальних endpoints

### 7.4 CORS

- [ ] Налаштувати `config/cors.php`
- [ ] Environment-based origins (CORS_ALLOWED_ORIGINS)
- [ ] Credentials support для specific origins

### 7.5 Health Checks

- [ ] GET /health — basic health check
- [ ] GET /health/ready — database connectivity check

### 7.6 Logging

- [ ] Налаштувати structured logging в `config/logging.php`
- [ ] Daily rotating files (30-day retention)

### 7.7 Tests

- [ ] Feature: Exception handling responses
- [ ] Feature: Rate limiting
- [ ] Feature: CORS headers
- [ ] Feature: Health check endpoints

**Deliverable:** Production-ready API з proper error handling, rate limiting, health checks.

---

## Фаза 8: Інтеграційне тестування та фіналізація

### 8.1 End-to-End тести

- [ ] Повний auth flow: register → login → refresh → revoke
- [ ] Повний OAuth flow: authorize URL → callback → login
- [ ] Address management flow: create → update → set primary → delete
- [ ] Meter flow: create meter → submit batch readings → verify calculations → export
- [ ] User flow: update profile → upload avatar → change password

### 8.2 API Compatibility Testing

- [ ] Порівняти JSON responses між .NET та Laravel для кожного ендпоінту
- [ ] Верифікувати pagination format
- [ ] Верифікувати error response format
- [ ] Верифікувати auth token compatibility

### 8.3 Performance

- [ ] Перевірити N+1 queries (eager loading)
- [ ] Верифікувати queue processing для зображень
- [ ] Load testing основних ендпоінтів

### 8.4 Code Quality

- [ ] Laravel Pint — форматування
- [ ] PHPStan level 5+ (поступове підвищення)
- [ ] Pest coverage report

### 8.5 Documentation

- [ ] Оновити CLAUDE.md з новою модульною структурою
- [ ] Оновити README.md

**Deliverable:** Production-ready API, повністю сумісне з .NET версією.

---

## Зведена таблиця

| Фаза | Модулі | Models | DTOs | Repos | Actions | Endpoints | Складність |
|------|--------|--------|------|-------|---------|-----------|------------|
| 0 | Інфраструктура | 0 | 0 | 1 base | 0 | 0 | Низька |
| 1 | Shared | 7 | 2 | 2 | 7 | 7 | Низька |
| 2 | Auth | 2 | 6 | 2 | 10 | 10 | Висока |
| 3 | Address | 4 | 3 | 4 | 9 | 7 | Середня |
| 4 | Billing | 2 | 4 | 2 | 8 | 6 | Середня |
| 5 | Meter | 2* | 5 | 2 | 14 | 17 | Висока |
| 6 | Users + Export | 0 | 1 | 0 | 9 | 8 | Середня |
| 7 | Cross-cutting | 0 | 0 | 0 | 0 | 2 | Середня |
| 8 | Тестування | 0 | 0 | 0 | 0 | 0 | Середня |
| **Разом** | **6 модулів** | **17*** | **21** | **13** | **57** | **~59** | |

*\* Meter module: 2 моделі замість 4 — MeterReadingPhoto та MeterReadingImage замінені на spatie/laravel-medialibrary `media` таблицю*

---

## Порядок залежностей модулів

```
Фаза 0: Інфраструктура
    ↓
Фаза 1: Shared (UtilityType, Currency — незалежні)
    ↓
Фаза 2: Auth (User — незалежний, потрібен для всіх protected endpoints)
    ↓
Фаза 3: Address (залежить від Auth: UserAddress, Shared: Region)
    ↓
Фаза 4: Billing (залежить від Address, Shared: UtilityType, Currency)
    ↓
Фаза 5: Meter (залежить від Address, Billing, Shared)
    ↓
Фаза 6: Users + Export (залежить від Auth, Meter, Billing)
    ↓
Фаза 7: Cross-cutting (після всіх модулів)
    ↓
Фаза 8: Інтеграційне тестування
```

---

## Ризики та мітигація

| Ризик | Ймовірність | Вплив | Мітигація |
|-------|-------------|-------|-----------|
| nwidart/laravel-modules не підтримує Laravel 12 | Низька | Високий | Перевірити `--dry-run` перед початком. Fallback: ручна модульна структура |
| JWT token format несумісний з існуючими клієнтами | Середня | Високий | Тестувати JWT decode на клієнті на ранній фазі (Фаза 2) |
| HEIC обробка не працює в Docker | Низька | Середній | Встановити imagick + libheif в Dockerfile |
| Pest 4 з модулями потребує ручної конфігурації | Висока | Низький | Pest.php в кожному модулі + phpunit.xml оновлення |
| Різниця в float precision (C# decimal vs PHP float) | Середня | Середній | Використовувати `bcmath` для фінансових розрахунків |
| N+1 queries при eager loading cross-module | Середня | Середній | Профілювати з Laravel Debugbar на кожній фазі |
