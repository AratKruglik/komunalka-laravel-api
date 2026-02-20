# План реалізації: Міграція Komunalka API (.NET → Laravel)

## Огляд

Міграція розбита на 8 фаз, кожна з яких є самодостатнім milestone. Фази виконуються послідовно, кожна будує на результатах попередньої.

---

## Фаза 0: Підготовка інфраструктури

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

- [ ] Опублікувати конфігурацію: `vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider"`
- [ ] Оновити `composer.json` — додати `"Modules\\": "Modules/"` до PSR-4 autoload
- [ ] Оновити `phpunit.xml` — додати `Modules/*/tests/Feature` та `Modules/*/tests/Unit`
- [ ] Налаштувати generator paths в `config/modules.php` для DDD структури

### 0.2a Конфігурація spatie/laravel-medialibrary

- [ ] Опублікувати міграцію: `vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"`
- [ ] Опублікувати конфігурацію: `vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-config"`
- [ ] Налаштувати disk, max_file_size, queue в `config/media-library.php`
- [ ] Запустити міграцію для створення `media` таблиці

### 0.2b Base Repository та DTO інфраструктура

- [ ] Створити `app/Repositories/Contracts/RepositoryInterface.php` — base interface (all, find, findOrFail, create, update, delete, paginate)
- [ ] Створити `app/Repositories/EloquentRepository.php` — base Eloquent implementation
- [ ] Документація: конвенція іменування DTO (`*Data.php`), Repository (`*Repository.php`, `*RepositoryInterface.php`)

### 0.3 Конфігурація JWT

- [ ] Опублікувати конфігурацію: `vendor:publish --provider="PHPOpenSourceSaver\JWTAuth\Providers\LaravelServiceProvider"`
- [ ] Згенерувати секрет: `php artisan jwt:secret`
- [ ] Налаштувати claims, TTL, refresh TTL в `config/jwt.php`

### 0.4 Конфігурація Socialite

- [ ] Додати Google та GitHub credentials до `.env`
- [ ] Налаштувати providers в `config/services.php`

### 0.5 Docker — imagick extension (для spatie/laravel-medialibrary HEIC підтримки)

- [ ] Додати `php-imagick` та `libheif` до Dockerfile
- [ ] Перебілдити контейнери

### 0.6 Структура каталогу docs/

- [x] PRD.md
- [x] ADR.md
- [x] IMPLEMENTATION_PLAN.md

**Deliverable:** Всі пакети встановлені, конфігурації опубліковані, Docker оновлений.

---

## Фаза 1: Shared Module — базові entities та seeders

### 1.1 Створення модуля

```bash
docker compose exec app php artisan module:make Shared
```

### 1.2 Моделі

- [ ] `UtilityType` — id, slug, display_name, unit, description, is_active, created_at, updated_at
  - Зв'язки: hasMany(Meter), hasMany(Tariff), hasMany(ServiceProvider)
  - Scope: `active()`
- [ ] `Currency` — id, code, name, symbol, created_at, updated_at
  - Зв'язки: hasMany(Tariff)
- [ ] `ServiceCategory` — id, name, created_at, updated_at
  - Зв'язки: belongsToMany(Address)
- [ ] `ServiceCounter` — id, address_id, service_category_id, serial_number, service_counter_measurement_id, created_at, updated_at
- [ ] `ServiceCounterMeasurement` — id, name, measurement, created_at, updated_at
- [ ] `ServiceCounterValue` — id, service_counter_id, value (float), created_at, updated_at
- [ ] `AddressesServiceCategory` (pivot model)

### 1.3 Міграції

- [ ] `create_utility_types_table` — з seed data (6 типів)
- [ ] `create_currencies_table`
- [ ] `create_service_categories_table`
- [ ] `create_service_counter_measurements_table`
- [ ] `create_service_counters_table`
- [ ] `create_service_counter_values_table`
- [ ] `create_address_service_category_table`

### 1.4 Seeders

- [ ] `UtilityTypeSeeder` — electricity, gas, cold-water, hot-water, heating, sewage
- [ ] `CurrencySeeder` — UAH (₴) як мінімум

### 1.5 Factories

- [ ] Factories для кожної моделі

### 1.5a Repositories

- [ ] `CurrencyRepositoryInterface` — all, find, create, update, delete
- [ ] `CurrencyRepository` (extends EloquentRepository)
- [ ] `UtilityTypeRepositoryInterface` — all, findBySlug, getActive
- [ ] `UtilityTypeRepository` (extends EloquentRepository)
- [ ] Bind interfaces в `SharedServiceProvider`

### 1.5b DTOs

- [ ] `CreateCurrencyData` — code, name, symbol (readonly, fromRequest)
- [ ] `UpdateCurrencyData` — code, name, symbol (readonly, fromRequest)

### 1.6 Actions (CRUD для Currency)

- [ ] `GetAllCurrencies` — список всіх валют
- [ ] `GetCurrency` — отримання за ID
- [ ] `CreateCurrency` — створення
- [ ] `UpdateCurrency` — оновлення
- [ ] `DeleteCurrency` — видалення

### 1.7 Actions (Read-only для UtilityType)

- [ ] `GetActiveUtilityTypes` — список активних
- [ ] `GetUtilityType` — отримання за ID

### 1.8 HTTP Layer

- [ ] `CurrencyController` — CRUD (public, no auth)
- [ ] `UtilityTypeController` — GET only (public)
- [ ] Form Requests: `StoreCurrencyRequest`, `UpdateCurrencyRequest`
- [ ] API Resources: `CurrencyResource`, `UtilityTypeResource`
- [ ] Routes: `api/v1/currency/*`, `api/v1/utility-types/*`

### 1.9 Tests

- [ ] Feature tests: Currency CRUD endpoints
- [ ] Feature tests: UtilityType GET endpoints
- [ ] Unit tests: Actions

**Deliverable:** Currency та UtilityType ендпоінти працюють, seed data доступна.

---

## Фаза 2: Auth Module — автентифікація

### 2.1 Створення модуля

```bash
docker compose exec app php artisan module:make Auth
```

### 2.2 Моделі

- [ ] `User` — всі поля з PRD, implements JWTSubject
  - Зв'язки: hasMany(UserAddress), hasMany(RefreshToken)
  - Unique: email, compound (auth_provider, external_id)
  - Casts: email_verified (boolean), last_login_at (datetime)
  - Hidden: password, remember_token
- [ ] `RefreshToken` — token, expiry_date, is_used, is_revoked, user_id
  - Зв'язки: belongsTo(User)

### 2.3 Міграції

- [ ] `create_users_table` — всі поля включаючи avatar та OAuth fields
- [ ] `create_refresh_tokens_table`

### 2.4 Seeders та Factories

- [ ] `UserFactory` — з states: admin, oauth_google, oauth_github, with_avatar
- [ ] `RefreshTokenFactory`

### 2.4a Repositories

- [ ] `UserRepositoryInterface` — findByEmail, findByExternalId(provider, externalId), create, update, delete
- [ ] `UserRepository` (extends EloquentRepository)
- [ ] `RefreshTokenRepositoryInterface` — findByToken, create, revokeForUser, deleteExpired
- [ ] `RefreshTokenRepository` (extends EloquentRepository)
- [ ] Bind interfaces в `AuthServiceProvider`

### 2.4b DTOs

- [ ] `RegisterUserData` — username, firstName, lastName, phoneNumber, email, password (readonly, fromRequest)
- [ ] `LoginData` — email, password (readonly, fromRequest)
- [ ] `RefreshTokenData` — refreshToken (readonly, fromRequest)
- [ ] `OAuthLoginData` — provider, token (readonly, fromRequest)
- [ ] `OAuthCallbackData` — provider, code, state, error, errorDescription (readonly, fromRequest)
- [ ] `UpdateUserData` — username, firstName, lastName, phoneNumber, email, currentPassword, newPassword (readonly, fromRequest)

### 2.5 Services

- [ ] `JwtService` — generateToken, generateRefreshToken, validateExpiredToken, getTokenExpiration
  - Custom claims: sub, email, name, role, jti
  - Конфігурація: JWT_EXPIRATION_MINUTES (30), JWT_REFRESH_TOKEN_EXPIRATION_DAYS (7)
- [ ] `OAuthService` — handleGoogleAuth, handleGitHubAuth, generateUniqueUsername
  - Інтеграція з Socialite
  - CSRF state через Cache (5 хв)

### 2.6 Actions

- [ ] `RegisterUser` — валідація, BCrypt hash, JWT generation
- [ ] `LoginUser` — email/password auth, prevent OAuth-only users
- [ ] `RefreshToken` — validate used/revoked/expired, issue new pair
- [ ] `RevokeToken` — mark as revoked
- [ ] `ValidateToken` — check JWT validity
- [ ] `OAuthLogin` — authenticate via provider token
- [ ] `GetOAuthUrl` — generate authorization URL
- [ ] `HandleOAuthCallback` — process auth code callback
- [ ] `LinkOAuthProvider` — link provider to existing user
- [ ] `UnlinkOAuthProvider` — unlink provider (requires password)

### 2.7 HTTP Layer

- [ ] `AuthController` — route-to-action mapping
- [ ] Form Requests: `LoginRequest`, `RegisterRequest`, `RefreshTokenRequest`, `OAuthLoginRequest`, `OAuthCallbackRequest`
- [ ] API Resources: `AuthenticationResponse`
- [ ] Routes: `api/v1/auth/*`
- [ ] Middleware: JWT auth guard registration

### 2.8 Tests

- [ ] Feature: register, login, refresh, revoke, validate
- [ ] Feature: OAuth login, callback, link, unlink
- [ ] Unit: JwtService token generation/validation
- [ ] Unit: RegisterUser action, LoginUser action

**Deliverable:** Повний auth flow працює — register, login, JWT, refresh, OAuth.

---

## Фаза 3: Address Module — адреси та регіони

### 3.1 Створення модуля

```bash
docker compose exec app php artisan module:make Address
```

### 3.2 Моделі

- [ ] `Address` — всі поля, soft deletes
  - Зв'язки: belongsTo(Region), belongsTo(AddressType), belongsToMany(User via UserAddress), hasMany(Meter), hasMany(ServiceProvider)
- [ ] `AddressType` — name, description, icon
  - Зв'язки: hasMany(Address)
- [ ] `Region` — name
  - Зв'язки: hasMany(Address)
- [ ] `UserAddress` (pivot model, table: address_user) — user_id, address_id, is_primary
  - Unique composite: (user_id, address_id)
  - Unique filtered: тільки один is_primary=true per user

### 3.3 Міграції

- [ ] `create_regions_table`
- [ ] `create_address_types_table`
- [ ] `create_addresses_table` — з soft deletes
- [ ] `create_address_user_table` — з unique constraints

### 3.4 Seeders

- [ ] `RegionSeeder` — 25 областей України
- [ ] `AddressTypeSeeder` — Квартира, Приватний будинок, Офіс

### 3.5 Factories

- [ ] `AddressFactory`, `AddressTypeFactory`, `RegionFactory`

### 3.5a Repositories

- [ ] `AddressRepositoryInterface` — getForUser(userId, perPage, sortBy, desc), findForUser(userId, addressId), createWithPivot, softDelete
- [ ] `AddressRepository` (extends EloquentRepository)
- [ ] `AddressTypeRepositoryInterface` — all, find
- [ ] `AddressTypeRepository`
- [ ] `RegionRepositoryInterface` — all, find
- [ ] `RegionRepository`
- [ ] `UserAddressRepositoryInterface` — getByUserId, setPrimary, removePrimary
- [ ] `UserAddressRepository`
- [ ] Bind interfaces в `AddressServiceProvider`

### 3.5b DTOs

- [ ] `CreateAddressData` — regionId, city, street, buildingNumber, apartmentNumber, zipCode, notes, isPrimary, addressTypeId (readonly, fromRequest)
- [ ] `UpdateAddressData` — regionId, city, street, buildingNumber, apartmentNumber, zipCode, notes, isPrimary, addressTypeId (readonly, fromRequest)
- [ ] `AddressPaginationData` — page, perPage, sortBy, desc (readonly, fromRequest/query)

### 3.6 Actions

- [ ] `GetUserAddresses` — пагінація, сортування (city, updatedAt, isPrimary, createdAt)
- [ ] `GetUserAddress` — з перевіркою доступу через UserAddress
- [ ] `CreateAddress` — створення + UserAddress pivot + primary address logic
- [ ] `UpdateAddress` — оновлення + primary address enforcement
- [ ] `DeleteAddress` — soft delete + pivot cleanup
- [ ] `GetAllAddressTypes` — список типів
- [ ] `GetAddressType` — тип за ID
- [ ] `GetAllRegions` — список регіонів
- [ ] `GetRegion` — регіон за ID

### 3.7 HTTP Layer

- [ ] `AddressController` — CRUD з пагінацією
- [ ] `AddressTypeController` — GET only
- [ ] `RegionController` — GET only
- [ ] Form Requests: `StoreAddressRequest`, `UpdateAddressRequest` (з українськими повідомленнями)
- [ ] API Resources: `AddressResource` (з nested Region, AddressType), `AddressTypeResource`, `RegionResource`
- [ ] Routes: `api/v1/address/*`, `api/v1/addresstype/*`, `api/v1/region/*`

### 3.8 Tests

- [ ] Feature: Address CRUD з пагінацією та сортуванням
- [ ] Feature: Multi-tenancy (user A не бачить адреси user B)
- [ ] Feature: Primary address logic
- [ ] Feature: AddressType та Region endpoints
- [ ] Unit: CreateAddress, UpdateAddress, DeleteAddress actions

**Deliverable:** Повне управління адресами, регіонами, типами адрес з multi-tenancy.

---

## Фаза 4: Billing Module — постачальники та тарифи

### 4.1 Створення модуля

```bash
docker compose exec app php artisan module:make Billing
```

### 4.2 Моделі

- [ ] `ServiceProvider` — name, description, phone, email, website, address_id, utility_type_id, is_active
  - Зв'язки: belongsTo(Address), belongsTo(UtilityType), hasMany(Meter), hasMany(Tariff)
  - Cascade delete від Address, Restrict від UtilityType
- [ ] `Tariff` — service_provider_id, utility_type_id, currency_id, name, base_rate, service_fee, effective_from, effective_to, notes
  - Зв'язки: belongsTo(ServiceProvider), belongsTo(UtilityType), belongsTo(Currency)
  - Scope: `effectiveAt(DateTime $date)` — фільтр за EffectiveFrom/To

### 4.3 Міграції

- [ ] `create_service_providers_table`
- [ ] `create_tariffs_table`

### 4.4 Factories

- [ ] `ServiceProviderFactory`, `TariffFactory`

### 4.4a Repositories

- [ ] `ServiceProviderRepositoryInterface` — getByAddressIds, getByAddressId, findWithTariffs, create, update, delete
- [ ] `ServiceProviderRepository`
- [ ] `TariffRepositoryInterface` — getEffective(meterId, date), create, update
- [ ] `TariffRepository`
- [ ] Bind interfaces в `BillingServiceProvider`

### 4.4b DTOs

- [ ] `CreateServiceProviderData` — addressId, utilityTypeId, name, description, phone, email, website, isActive, tariffs[] (readonly, fromRequest)
- [ ] `UpdateServiceProviderData` — name, description, phone, email, website, isActive, utilityTypeId (readonly, fromRequest)
- [ ] `CreateTariffData` — utilityTypeId, currencyId, name, baseRate, serviceFee, effectiveFrom, effectiveTo, notes (readonly)
- [ ] `TariffCalculationResult` — meterId, meterName, consumption, unit, baseRate, serviceFee, totalCost, currencyCode, currencySymbol (readonly)

### 4.5 Actions

- [ ] `GetUserServiceProviders` — постачальники для адрес поточного користувача
- [ ] `GetServiceProvider` — за ID з тарифами
- [ ] `GetServiceProvidersByAddress` — за addressId
- [ ] `CreateServiceProvider` — створення з вкладеними тарифами
- [ ] `UpdateServiceProvider` — оновлення
- [ ] `DeleteServiceProvider` — видалення
- [ ] `GetEffectiveTariff` — пошук діючого тарифу за meterId + date
- [ ] `CalculateTariffCost` — розрахунок: (consumption × BaseRate) + ServiceFee

### 4.6 HTTP Layer

- [ ] `ServiceProviderController` — CRUD
- [ ] Form Requests: `StoreServiceProviderRequest`, `UpdateServiceProviderRequest`
- [ ] API Resources: `ServiceProviderResource`, `ServiceProviderWithTariffsResource`, `TariffResource`
- [ ] Routes: `api/v1/service-providers/*`

### 4.7 Tests

- [ ] Feature: ServiceProvider CRUD
- [ ] Feature: Authorization (user owns address)
- [ ] Unit: CalculateTariffCost з різними сценаріями
- [ ] Unit: GetEffectiveTariff — date range logic

**Deliverable:** Повне управління постачальниками та тарифами з розрахунками.

---

## Фаза 5: Meter Module — лічильники та показники

### 5.1 Створення модуля

```bash
docker compose exec app php artisan module:make Meter
```

### 5.2 Моделі

- [ ] `Meter` — address_id, utility_type_id, serial_number, name, description, model_name, location, installation_date, initial_reading, service_provider_id, notes, is_active
  - Зв'язки: belongsTo(Address), belongsTo(UtilityType), belongsTo(ServiceProvider nullable), hasMany(MeterReading)
  - Scope: `active()`, `forAddress(int $addressId)`
  - **Implements HasMedia** — media collection `photo` (singleFile) для фото лічильника
- [ ] `MeterReading` — meter_id, reading_value, reading_date, previous_reading_value, consumption, notes, is_estimated, tariff_id
  - Зв'язки: belongsTo(Meter), belongsTo(Tariff nullable)
  - **Implements HasMedia** — media collection `photos` (multiple) для фото показників
  - Conversions: `optimized` (800px, JPEG 85%), `thumbnail` (200px, JPEG 85%) — queued
- [ ] ~~MeterReadingPhoto~~ — **ЗАМІНЕНО** на spatie/laravel-medialibrary `media` таблицю
- [ ] ~~MeterReadingImage~~ — **ЗАМІНЕНО** на spatie/laravel-medialibrary `media` таблицю

### 5.3 Міграції

- [ ] `create_meters_table` — FK: address_id, utility_type_id, service_provider_id (nullable, onDelete SET NULL)
- [ ] `create_meter_readings_table` — FK: meter_id, tariff_id (nullable)
- [ ] ~~create_meter_reading_photos_table~~ — НЕ потрібна (spatie media table)
- [ ] ~~create_meter_reading_images_table~~ — НЕ потрібна (spatie media table)

### 5.4 Factories

- [ ] `MeterFactory`, `MeterReadingFactory`

### 5.4a Repositories

- [ ] `MeterRepositoryInterface` — getByAddressId, getActive, findWithRelations, create, update, delete
- [ ] `MeterRepository`
- [ ] `MeterReadingRepositoryInterface` — getByAddressId(from, to), getByMeterId, findWithPhotos, create, delete
- [ ] `MeterReadingRepository`
- [ ] Bind interfaces в `MeterServiceProvider`

### 5.4b DTOs

- [ ] `CreateMeterData` — addressId, utilityTypeId, serialNumber, name, description, modelName, location, installationDate, initialReading, serviceProviderId, notes, isActive (readonly, fromRequest)
- [ ] `UpdateMeterData` — serialNumber, name, description, modelName, location, installationDate, initialReading, serviceProviderId, notes, isActive (all nullable, readonly, fromRequest)
- [ ] `CreateMeterReadingData` — meterId, readingValue, readingDate, notes, isEstimated, tariffId (readonly)
- [ ] `BatchReadingData` — addressId, readings: CreateMeterReadingData[] (readonly, fromRequest)
- [ ] `ExportRequestData` — addressIds[], fromDate, toDate, format (readonly, fromRequest)

### 5.5 Media (spatie/laravel-medialibrary)

Замість custom ImageService/FileStorageService/Jobs:
- [ ] Визначити media collections на `MeterReading` model: `photos` (multiple, mime: jpeg/png/webp, max 10MB)
- [ ] Визначити media collections на `Meter` model: `photo` (singleFile, mime: jpeg/png/webp)
- [ ] Визначити conversions: `optimized` (800px, quality 85, jpg, queued) та `thumbnail` (200px, quality 85, jpg, queued)
- [ ] Налаштувати queue connection для media conversions в `config/media-library.php`

### 5.7 Actions

#### Meter CRUD
- [ ] `GetAllMeters` — всі лічильники
- [ ] `GetMeter` — за ID
- [ ] `GetMetersByAddress` — за addressId
- [ ] `GetActiveMeters` — тільки активні
- [ ] `CreateMeter` — створення (JSON body)
- [ ] `UploadMeterPhoto` — завантаження фото лічильника (через medialibrary: `$meter->addMedia()->toMediaCollection('photo')`)
- [ ] `UpdateMeter` — оновлення (partial update)
- [ ] `DeleteMeter` — видалення

#### Meter Reading
- [ ] `CreateBatchReadings` — пакетне створення з фото та розрахунками тарифів
  - Валідація: meter belongs to address, is active, reading >= previous
  - Авто-визначення тарифу
  - Розрахунок consumption та cost
  - Фото: `$reading->addMedia($file)->toMediaCollection('photos')` — conversions queued автоматично
- [ ] `GetReadingsByAddress` — за addressId з date range filter
- [ ] `GetMeterReading` — за ID
- [ ] `DeleteMeterReading` — видалення + cleanup файлів

#### Legacy MeterReading (ServiceCounterValue)
- [ ] `CreateServiceCounterValue` — створення показника (legacy)
- [ ] `GetServiceCounterValue` — отримання за ID
- [ ] `DeleteServiceCounterValue` — видалення

### 5.8 HTTP Layer

- [ ] `MeterController` — CRUD
- [ ] `BatchMeterReadingsController` — batch create, get by address, photos serving
- [ ] `MeterReadingController` — legacy endpoints
- [ ] Form Requests: `StoreMeterRequest`, `UpdateMeterRequest`, `StoreMeterReadingRequest`, `BatchMeterReadingRequest`
- [ ] API Resources: `MeterResource`, `MeterReadingResource` (з media URLs через `getFirstMediaUrl`), `BatchMeterReadingResponse`, `TariffCalculationResource`
- [ ] Routes: `api/v1/meter/*`, `api/v1/meter-readings/*`, `api/v1/meterreading/*`
- [ ] Photo serving endpoints: використовують `$reading->getFirstMediaUrl('photos', 'optimized')` та `thumbnail`

### 5.9 Tests

- [ ] Feature: Meter CRUD
- [ ] Feature: Batch readings creation з тарифними розрахунками
- [ ] Feature: Photo upload та serving
- [ ] Feature: Multi-tenancy validation
- [ ] Feature: Reading value validation (>= previous)
- [ ] Unit: CreateBatchReadings action
- [ ] Unit: ImageService processing
- [ ] Integration: Full flow — create meter → submit readings → verify calculations

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
