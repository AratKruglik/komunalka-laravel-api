# ADR: Архітектурні рішення для міграції Komunalka API

## ADR-001: Модульна архітектура (DDD) з nwidart/laravel-modules

### Статус
Прийнято

### Контекст
.NET проєкт організований за класичним layered pattern (Controllers → Services → Repositories → Models). При міграції на Laravel маємо можливість покращити архітектуру, впровадивши Domain-Driven Design через модульну структуру.

### Рішення
Використовуємо `nwidart/laravel-modules` для організації коду в bounded contexts:

```
Modules/
├── Auth/
├── Address/
├── Meter/
├── Billing/
├── Export/
└── Shared/
```

### Структура модуля

```
Modules/Auth/
├── app/
│   ├── Actions/                ← lorisleiva/laravel-actions
│   │   ├── LoginUser.php
│   │   ├── RegisterUser.php
│   │   └── ...
│   ├── DTOs/                   ← Data Transfer Objects (readonly)
│   │   ├── LoginData.php
│   │   ├── RegisterUserData.php
│   │   └── ...
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── AuthController.php
│   │   ├── Requests/           ← Form Request validation
│   │   │   ├── LoginRequest.php
│   │   │   └── RegisterRequest.php
│   │   └── Resources/          ← API Resources (response serialization)
│   │       └── AuthResponse.php
│   ├── Models/
│   │   ├── User.php
│   │   └── RefreshToken.php
│   ├── Providers/
│   │   ├── AuthServiceProvider.php   ← bind Repository interfaces
│   │   └── RouteServiceProvider.php
│   ├── Repositories/           ← Repository pattern
│   │   ├── Contracts/
│   │   │   ├── UserRepositoryInterface.php
│   │   │   └── RefreshTokenRepositoryInterface.php
│   │   ├── UserRepository.php
│   │   └── RefreshTokenRepository.php
│   └── Services/               ← складна логіка, що не вписується в Action
│       ├── JwtService.php
│       └── OAuthService.php
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── api.php
├── tests/
│   ├── Feature/
│   ├── Unit/
│   └── Pest.php
└── module.json
```

### Потік даних через шари

```
HTTP Request
    ↓
Form Request (validation)
    ↓
DTO::fromRequest() (typed data object)
    ↓
Controller → Action::run(DTO)
    ↓
Action::handle() (business logic, calls Repositories)
    ↓
Repository (data access via Eloquent)
    ↓
Model (Eloquent entity)
    ↓
API Resource (response serialization)
    ↓
JSON Response
```

### Наслідки

**Позитивні:**
- Чітке розділення bounded contexts
- Незалежна розробка та тестування модулів
- Легше масштабування команди
- Можливість вимкнути/увімкнути модуль

**Негативні:**
- Складніша початкова настройка
- Глибші namespace (`Modules\Auth\Actions\LoginUser`)
- Потрібна ручна реєстрація Actions для модулів
- Factory discovery потребує override `newFactory()` на моделях

---

## ADR-002: Actions замість Service Layer

### Статус
Прийнято

### Контекст
.NET проєкт використовує Service + Repository pattern. В Laravel Eloquent вже є Active Record (ORM), що робить окремий Repository layer зайвим. Service classes в .NET часто занадто великі (MeterReadingService — 300+ рядків).

### Рішення
Використовуємо `lorisleiva/laravel-actions` як основний патерн для бізнес-логіки. Кожна Action — один бізнес-процес.

**Mapping .NET Services → Laravel Actions:**

| .NET Service Method | Laravel Action |
|---|---|
| AddressService.CreateAsync() | Modules\Address\Actions\CreateAddress |
| AddressService.UpdateAsync() | Modules\Address\Actions\UpdateAddress |
| AddressService.DeleteAsync() | Modules\Address\Actions\DeleteAddress |
| AddressService.GetForUserAsync() | Modules\Address\Actions\GetUserAddresses |
| AuthService.AuthenticateAsync() | Modules\Auth\Actions\LoginUser |
| AuthService.RegisterAsync() | Modules\Auth\Actions\RegisterUser |
| AuthService.RefreshTokenAsync() | Modules\Auth\Actions\RefreshToken |
| MeterReadingService.CreateBatchAsync() | Modules\Meter\Actions\CreateBatchReadings |
| TariffCalculationService.CalculateCostAsync() | Modules\Billing\Actions\CalculateTariffCost |
| ExportService.GenerateCsv() | Modules\Export\Actions\ExportToCsv |
| ExportService.GeneratePdf() | Modules\Export\Actions\ExportToPdf |

**Правила використання Actions:**
1. `handle()` — чиста бізнес-логіка, без HTTP-залежностей
2. `asController()` — HTTP-адаптер (лише якщо Action використовується як route handler)
3. `rules()` — НЕ використовуємо (Form Request замість цього, згідно CLAUDE.md)
4. Actions можуть викликати інші Actions для композиції

**Коли НЕ використовувати Actions:**
- Складні cross-cutting сервіси (JwtService, OAuthService, ImageService) — залишаємо як класичні Service classes
- Background jobs — окремі Job класи (але можуть dispatch Actions)

### Наслідки

**Позитивні:**
- Один клас = одна відповідальність
- Легке тестування (CreateAddress::run(...))
- Легка композиція (RegisterUser викликає CreateUser + SendWelcomeEmail)
- Можливість dispatch як Job

**Негативні:**
- Більше файлів порівняно з Service classes
- Потрібна дисципліна в іменуванні (verb + noun: CreateAddress, не AddressCreator)

---

## ADR-003: Repository Pattern

### Статус
Прийнято

### Контекст
.NET проєкт використовує Repository pattern з UnitOfWork (15+ репозиторіїв). Потрібно зберегти цей архітектурний підхід для:
- Абстракції доступу до даних
- Тестованості (можливість мокати репозиторії)
- Єдиного місця для складних запитів

### Рішення
Реалізуємо Repository pattern з interface-based підходом всередині кожного модуля.

**Структура:**
```
Modules/Address/
├── app/
│   ├── Repositories/
│   │   ├── Contracts/
│   │   │   └── AddressRepositoryInterface.php
│   │   └── AddressRepository.php
│   └── Providers/
│       └── AddressServiceProvider.php  ← bind interface → implementation
```

**Base Repository Interface:**
```php
namespace App\Repositories\Contracts;

interface RepositoryInterface
{
    public function all(): Collection;
    public function find(int $id): ?Model;
    public function findOrFail(int $id): Model;
    public function create(array $data): Model;
    public function update(int $id, array $data): Model;
    public function delete(int $id): bool;
    public function paginate(int $perPage = 15): LengthAwarePaginator;
}
```

**Eloquent Base Repository:**
```php
namespace App\Repositories;

abstract class EloquentRepository implements RepositoryInterface
{
    public function __construct(protected Model $model) {}

    public function all(): Collection
    {
        return $this->model->newQuery()->get();
    }
    // ... інші методи
}
```

**Module Repository (з domain-specific методами):**
```php
namespace Modules\Address\Repositories;

class AddressRepository extends EloquentRepository implements AddressRepositoryInterface
{
    public function __construct(Address $model)
    {
        parent::__construct($model);
    }

    public function getForUser(int $userId, int $perPage, string $sortBy, bool $desc): LengthAwarePaginator
    {
        return $this->model->newQuery()
            ->whereHas('userAddresses', fn ($q) => $q->where('user_id', $userId))
            ->with(['region', 'addressType'])
            ->orderBy($sortBy, $desc ? 'desc' : 'asc')
            ->paginate($perPage);
    }
}
```

**Binding в ServiceProvider:**
```php
$this->app->bind(AddressRepositoryInterface::class, AddressRepository::class);
```

**Mapping .NET Repositories → Laravel:**

| .NET Repository | Laravel Repository |
|---|---|
| IAddressRepository | Modules\Address\Repositories\Contracts\AddressRepositoryInterface |
| IUserRepository | Modules\Auth\Repositories\Contracts\UserRepositoryInterface |
| IMeterRepository | Modules\Meter\Repositories\Contracts\MeterRepositoryInterface |
| IMeterReadingRepository | Modules\Meter\Repositories\Contracts\MeterReadingRepositoryInterface |
| IServiceProviderRepository | Modules\Billing\Repositories\Contracts\ServiceProviderRepositoryInterface |
| ITariffRepository | Modules\Billing\Repositories\Contracts\TariffRepositoryInterface |
| ICurrencyRepository | Modules\Shared\Repositories\Contracts\CurrencyRepositoryInterface |
| IRegionRepository | Modules\Address\Repositories\Contracts\RegionRepositoryInterface |
| IRefreshTokenRepository | Modules\Auth\Repositories\Contracts\RefreshTokenRepositoryInterface |

**Правила:**
- Actions інжектять Repository interfaces, не конкретні класи
- Складні запити — в Repository, не в Actions
- Eloquent scopes залишаються на моделях для простих фільтрів (active, forAddress)
- Repository не повертає Query Builder назовні — тільки Models/Collections/Paginators

### Наслідки

**Позитивні:**
- Тестованість — легко мокати через interface binding
- Абстракція — Actions не залежать від Eloquent напряму
- Єдине місце для складних запитів
- Паритет архітектури з .NET проєктом

**Негативні:**
- Додатковий layer (interface + implementation на кожен entity)
- Більше файлів на модуль
- Потрібна дисципліна щоб не дублювати логіку між Repository та Eloquent scopes

---

## ADR-003a: DTO Pattern (Data Transfer Objects)

### Статус
Прийнято

### Контекст
.NET проєкт активно використовує DTO (~50 класів) для передачі даних між шарами та серіалізації в JSON. Laravel має API Resources для серіалізації, але не має формалізованого DTO layer для вхідних даних та внутрішнього обміну.

### Рішення
Реалізуємо DTO як typed PHP classes з readonly properties для:
1. **Request DTOs** — structured input для Actions (замість масивів)
2. **Response DTOs** — НЕ використовуємо (Laravel API Resources виконують цю роль)

**Структура:**
```
Modules/Address/
├── app/
│   ├── DTOs/
│   │   ├── CreateAddressData.php
│   │   ├── UpdateAddressData.php
│   │   └── AddressPaginationData.php
```

**Приклад Request DTO:**
```php
namespace Modules\Address\DTOs;

final readonly class CreateAddressData
{
    public function __construct(
        public int $regionId,
        public string $city,
        public string $street,
        public string $buildingNumber,
        public ?string $apartmentNumber,
        public string $zipCode,
        public ?string $notes,
        public bool $isPrimary,
        public int $addressTypeId,
    ) {}

    public static function fromRequest(StoreAddressRequest $request): self
    {
        return new self(
            regionId: $request->validated('region_id'),
            city: $request->validated('city'),
            street: $request->validated('street'),
            buildingNumber: $request->validated('building_number'),
            apartmentNumber: $request->validated('apartment_number'),
            zipCode: $request->validated('zip_code'),
            notes: $request->validated('notes'),
            isPrimary: $request->validated('is_primary', false),
            addressTypeId: $request->validated('address_type_id'),
        );
    }
}
```

**Використання в Action:**
```php
class CreateAddress
{
    use AsAction;

    public function __construct(
        private AddressRepositoryInterface $addressRepository,
    ) {}

    public function handle(int $userId, CreateAddressData $data): Address
    {
        // Business logic використовує typed DTO замість array
        return $this->addressRepository->create([
            'region_id' => $data->regionId,
            'city' => $data->city,
            // ...
        ]);
    }
}
```

**Flow даних:**
```
HTTP Request → Form Request (validation) → DTO::fromRequest() → Action::handle(DTO) → Repository → Model
Model → API Resource → JSON Response
```

**Mapping .NET DTOs → Laravel:**

| .NET DTO | Laravel Equivalent |
|---|---|
| CreateAddressDto | Modules\Address\DTOs\CreateAddressData |
| UpdateAddressDto | Modules\Address\DTOs\UpdateAddressData |
| AddressDto (response) | Modules\Address\Http\Resources\AddressResource |
| RegisterUserRequest | Modules\Auth\DTOs\RegisterUserData |
| AuthenticationRequest | Modules\Auth\DTOs\LoginData |
| CreateMeterDto | Modules\Meter\DTOs\CreateMeterData |
| BatchMeterReadingDto | Modules\Meter\DTOs\BatchReadingData |
| CreateServiceProviderDto | Modules\Billing\DTOs\CreateServiceProviderData |
| ExportRequestDto | Modules\Export\DTOs\ExportRequestData |

**Правила:**
- DTO — завжди `final readonly class`
- Використовувати PHP 8.4 constructor promotion
- Factory method `fromRequest()` для конвертації з Form Request
- Response serialization — через API Resources (не DTO)
- DTO НЕ містять бізнес-логіки

### Наслідки

**Позитивні:**
- Type safety — Actions отримують typed objects замість масивів
- Self-documenting — явний контракт між controller та action
- Refactoring safety — IDE підкаже при зміні полів
- Immutability — readonly запобігає випадковій мутації

**Негативні:**
- Додаткові класи (1 DTO на Create/Update операцію)
- Дублювання полів між Form Request та DTO (мітигується через `fromRequest()`)

---

## ADR-004: JWT автентифікація

### Статус
Прийнято

### Контекст
.NET API використовує custom JWT implementation з refresh tokens. Потрібно відтворити ідентичний flow для сумісності з існуючими клієнтами.

### Рішення
Використовуємо `tymon/jwt-auth` (або `php-open-source-saver/jwt-auth` для Laravel 12) з custom refresh token моделлю.

**Чому не Sanctum:**
- Sanctum використовує opaque tokens, а не JWT
- Існуючі клієнти очікують JWT з claims (sub, email, name, role)
- Потрібна валідація expired tokens для refresh flow

**Чому не Passport:**
- Passport — повний OAuth2 server, це overhead
- Нам потрібен лише JWT issue/validate

**Структура:**
- `JwtService` — генерація/валідація JWT (аналог .NET JwtService)
- `RefreshToken` model — зберігання refresh tokens в БД
- Custom middleware для JWT validation
- Claims: sub (user_id), email, name (username), role, jti

### Наслідки
- Повна сумісність з існуючими клієнтами
- Контроль над token format та claims
- Потрібно самостійно реалізувати refresh flow

---

## ADR-005: OAuth через Laravel Socialite

### Статус
Прийнято

### Контекст
.NET використовує custom OAuth providers (Google, GitHub) з прямими HTTP-запитами до OAuth API. Laravel має офіційний пакет Socialite для цього.

### Рішення
Використовуємо `laravel/socialite` для OAuth інтеграції.

**Маппінг:**
- GoogleOAuthProvider → Socialite Google driver
- GitHubOAuthProvider → Socialite GitHub driver
- OAuthService → Modules\Auth\Actions\OAuthLogin, OAuthCallback, LinkProvider, UnlinkProvider

**Зберігаємо бізнес-логіку:**
- Авто-створення користувачів
- Авто-лінк існуючих (без паролю)
- CSRF state validation
- Унікальний username generation

### Наслідки
- Стандартний Laravel підхід
- Легко додати нових провайдерів
- Менше custom коду для OAuth flows

---

## ADR-006: Обробка зображень через spatie/laravel-medialibrary

### Статус
Прийнято

### Контекст
.NET використовує SixLabors.ImageSharp + Magick.NET для обробки зображень (фото лічильників, аватари) з фоновою обробкою через Channel-based queue. Потрібне рішення, що інтегрується з Eloquent моделями та надає:
- Зберігання файлів (optimized + thumbnail)
- Автоматичні image conversions
- Зв'язок media з моделями
- Queued processing

### Рішення
Використовуємо `spatie/laravel-medialibrary` — стандартний Laravel пакет для управління медіа-файлами, прив'язаними до Eloquent моделей.

**Чому spatie/laravel-medialibrary:**
- Автоматичні image conversions (optimized, thumbnail) без ручного коду
- Polymorphic зв'язок media → model (один пакет для MeterReading photos і User avatars)
- Queued conversions (Laravel Queue + Redis)
- Collections з правилами (singleFile для avatar, multiple для meter photos)
- URL generation для conversions
- HEIC/HEIF підтримка через imagick driver

**Чому НЕ intervention/image напряму:**
- Intervention Image — лише image manipulation, без зберігання та зв'язків
- Потрібно самостійно реалізовувати: file storage, model associations, URL generation, cleanup
- spatie/laravel-medialibrary використовує Intervention Image під капотом

**Реалізація на моделях:**

```php
// MeterReading model
class MeterReading extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('optimized')
            ->width(800)
            ->quality(85)
            ->format('jpg')
            ->queued();

        $this->addMediaConversion('thumbnail')
            ->width(200)
            ->quality(85)
            ->format('jpg')
            ->queued();
    }
}

// User model
class User extends Authenticatable implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()  // автоматично видаляє попередній аватар
            ->acceptsMimeTypes([
                'image/jpeg', 'image/png', 'image/gif',
                'image/heic', 'image/heif',
            ]);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('optimized')
            ->width(800)
            ->quality(85)
            ->format('jpg')
            ->performOnCollections('avatar')
            ->queued();

        $this->addMediaConversion('thumbnail')
            ->width(200)
            ->quality(85)
            ->format('jpg')
            ->performOnCollections('avatar')
            ->queued();
    }
}
```

**Використання в Actions:**
```php
// Upload meter reading photo
$meterReading->addMedia($uploadedFile)->toMediaCollection('photos');

// Upload avatar (singleFile — автоматично замінює попередній)
$user->addMedia($uploadedFile)->toMediaCollection('avatar');

// Get URLs
$optimizedUrl = $meterReading->getFirstMediaUrl('photos', 'optimized');
$thumbnailUrl = $meterReading->getFirstMediaUrl('photos', 'thumbnail');
$avatarUrl = $user->getFirstMediaUrl('avatar', 'optimized');
```

**Mapping .NET → Laravel medialibrary:**

| .NET компонент | Laravel medialibrary еквівалент |
|---|---|
| ImageService.ProcessImageAsync() | Queued media conversions (автоматично) |
| ImageService.ProcessAvatarImageAsync() | Queued media conversions + singleFile collection |
| FileStorageService | spatie disk configuration (config/media-library.php) |
| MeterReadingPhoto model | Media model (polymorphic, collection: 'photos') |
| MeterReadingImage model | Media model (polymorphic, collection: 'images') |
| User.AvatarOptimizedPath | `$user->getFirstMediaUrl('avatar', 'optimized')` |
| User.AvatarThumbnailPath | `$user->getFirstMediaUrl('avatar', 'thumbnail')` |
| ImageProcessingService (background) | Laravel Queue worker (автоматично) |
| StorageScope enum | Media collections |

**Параметри (збережені з .NET):**
- Optimized: 800px max width, JPEG 85%
- Thumbnail: 200px max width, JPEG 85%
- Avatar max upload: 2MB, formats: jpg, png, gif, heic, heif
- Meter reading max upload: 10MB, formats: jpg, png, webp
- Validation через Form Request (не medialibrary)

**Зміни в моделях:**
- `MeterReadingPhoto` та `MeterReadingImage` — **НЕ створюємо** як окремі Eloquent моделі. Їх замінює `media` таблиця з spatie/laravel-medialibrary (polymorphic `model_type` + `model_id`)
- Поля avatar_* на User моделі — **видаляємо** з міграції, замінюємо на media collection
- `is_processed` статус — відслідковуємо через `hasGeneratedConversion('optimized')`

### Наслідки

**Позитивні:**
- Значно менше custom коду (немає ImageService, FileStorageService, ProcessMeterReadingPhoto job)
- Автоматичне управління файлами (cleanup при видаленні моделі)
- singleFile collection для аватарів — автоматична заміна
- Queued conversions — без додаткових Job класів
- URL generation — без manual path construction
- Polymorphic — один підхід для всіх моделей

**Негативні:**
- Додаткова `media` таблиця в БД (замість окремих MeterReadingPhoto/MeterReadingImage)
- Зміна схеми відносно .NET (avatar fields на User замінюються на media relation)
- Потрібно встановити imagick в Docker для HEIC підтримки
- Overhead для простих випадків (лише один файл)

---

## ADR-007: Експорт даних

### Статус
Прийнято

### Контекст
.NET використовує custom CSV generator та QuestPDF для PDF. Потрібно відтворити обидва формати.

### Рішення
- **CSV**: використовуємо `league/csv` або native PHP `fputcsv()` з proper escaping
- **PDF**: використовуємо `barryvdh/laravel-dompdf` або `spatie/laravel-pdf` (Browsershot)

Рекомендовано `spatie/laravel-pdf` для кращої якості PDF.

### Наслідки
- Стандартні Laravel пакети
- PDF layout може дещо відрізнятись візуально (HTML-based vs QuestPDF)

---

## ADR-008: Використання існуючої БД без міграцій

### Статус
Прийнято

### Контекст
.NET API використовує існуючу PostgreSQL базу зі snake_case naming convention (вже Laravel-compatible). Міграції .NET створювали таблиці з Laravel-сумісними іменами.

### Рішення
Створюємо Laravel міграції, що відтворюють поточну структуру БД. Це дозволить:
1. Мати version-controlled schema
2. Запускати тести з чистою БД
3. Розгортати нові інстанси

**Підхід:**
- Міграції створюються на основі аналізу .NET schema
- Snake_case naming (вже використовується в .NET)
- Всі constraints, indexes, unique keys зберігаються
- Seed data відтворюється через Laravel seeders

### Наслідки
- Повна сумісність зі схемою .NET
- Можливість паралельної роботи обох API
- Тести можуть використовувати RefreshDatabase

---

## ADR-009: API Versioning

### Статус
Прийнято

### Контекст
.NET API використовує URL-based versioning (`/api/v1/...`).

### Рішення
Організовуємо routes з prefix `/api/v1` в кожному модулі. Для майбутніх версій створюємо окремі route files.

```php
// Modules/Auth/routes/api.php
Route::prefix('v1/auth')->group(function () {
    Route::post('/login', LoginUser::class);
});
```

### Наслідки
- Простий підхід без додаткових пакетів
- Легко додати v2 в майбутньому

---

## ADR-010: Тестування з Pest 4

### Статус
Прийнято

### Контекст
Потрібна стратегія тестування для модульної архітектури з Pest 4.

### Рішення

**Структура тестів:**
```
Modules/Auth/tests/
├── Feature/
│   ├── LoginTest.php
│   ├── RegisterTest.php
│   └── OAuthTest.php
├── Unit/
│   ├── Actions/
│   │   ├── LoginUserTest.php
│   │   └── RegisterUserTest.php
│   └── Services/
│       └── JwtServiceTest.php
└── Pest.php
```

**phpunit.xml** оновлюється для включення `Modules/*/tests/*`.

**Кожен модуль має Pest.php:**
```php
uses(Tests\TestCase::class)->in('Feature');
uses(Tests\TestCase::class)->in('Unit');
```

**Підхід до тестування:**
- Unit tests: Actions (handle method напряму)
- Feature tests: HTTP endpoints через Laravel test client
- Integration tests: бізнес-сценарії (batch readings, OAuth flow)

### Наслідки
- Pest 4 повністю сумісний з модулями (через phpunit.xml)
- Кожен модуль тестується незалежно
- `module:make-test` генерує PHPUnit-стиль, потрібно конвертувати в Pest

---

## ADR-011: Міжмодульна комунікація

### Статус
Прийнято

### Контекст
Модулі потребують доступу до моделей/Actions інших модулів (наприклад, Meter модуль потребує Address, Billing потребує UtilityType).

### Рішення

**Рівні зв'язності:**

1. **Shared Module** — спільні entities (UtilityType, Currency, ServiceCategory), до яких звертаються кілька модулів
2. **Read-only доступ** — модуль може читати моделі іншого модуля через їх public API (Eloquent models)
3. **Events** — для side effects (наприклад, після видалення Address — очистити пов'язані Meter readings)
4. **Actions** — модуль може викликати Actions іншого модуля

**Правила:**
- НІКОЛИ не імпортувати Actions з іншого модуля напряму в controller
- Моделі можна імпортувати cross-module (вони public)
- Складна cross-module логіка — через Events

**Граф залежностей:**
```
Shared ← Address ← Meter ← Export
                 ← Billing ←┘
Auth (ізольований, тільки User model використовується)
```

### Наслідки
- Чіткі межі між модулями
- Shared module запобігає circular dependencies
- Events забезпечують loose coupling
