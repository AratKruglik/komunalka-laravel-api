# ADR: Архітектурні рішення Komunalka

## ADR-001: Модульна архітектура (DDD) з nwidart/laravel-modules

### Статус
Прийнято

### Контекст
Додаток організований за принципом bounded contexts. Використання nwidart/laravel-modules дозволяє ізолювати домени, тестувати їх незалежно та контролювати зв'язки між модулями.

### Рішення
Використовуємо `nwidart/laravel-modules` для організації коду в шість доменів:

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
├── Actions/                    ← lorisleiva/laravel-actions
│   ├── LoginUser.php
│   ├── RegisterUser.php
│   └── ...
├── DTOs/                       ← Data Transfer Objects (readonly)
│   ├── LoginData.php
│   └── RegisterUserData.php
├── Http/
│   ├── Controllers/
│   │   └── Web/                ← Inertia controllers
│   │       ├── LoginController.php
│   │       └── RegisterController.php
│   └── Requests/               ← Form Request validation
│       ├── LoginRequest.php
│       └── RegisterRequest.php
├── Models/
│   └── User.php
├── Providers/
│   ├── AuthServiceProvider.php
│   └── RouteServiceProvider.php
├── Repositories/               ← Repository pattern (де потрібно)
│   ├── Contracts/
│   │   └── UserRepositoryInterface.php
│   └── UserRepository.php
├── config/
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── routes/
│   └── web.php                 ← виключно web маршрути
├── tests/
│   ├── Feature/
│   ├── Unit/
│   └── Pest.php
└── module.json
```

### Потік даних через шари

```
HTTP Request (web route)
    ↓
Form Request (validation)
    ↓
DTO::fromRequest() (typed data object)
    ↓
Controller → Action::handle(DTO)
    ↓
Action (business logic, Eloquent / Repository)
    ↓
Model (Eloquent entity)
    ↓
Inertia::render('Page/Name', $props)
    ↓
React TSX Component
```

### Наслідки

**Позитивні:**
- Чітке розділення bounded contexts
- Незалежна розробка та тестування модулів
- Легше масштабування
- Можливість вимкнути/увімкнути модуль

**Негативні:**
- Складніша початкова настройка
- Глибші namespace (`Modules\Auth\Actions\LoginUser`)
- Factory discovery потребує override `newFactory()` на моделях

---

## ADR-002: Actions замість Service Layer

### Статус
Прийнято

### Контекст
Laravel Eloquent вже є Active Record ORM, що робить окремий Service layer зайвим. Action класи забезпечують принцип єдиної відповідальності та легку тестованість.

### Рішення
Використовуємо `lorisleiva/laravel-actions` як основний патерн для бізнес-логіки. Кожна Action — один бізнес-процес.

**Приклади Actions по модулях:**

| Модуль | Action |
|--------|--------|
| Auth | LoginUser, RegisterUser, LinkOAuthProvider, UnlinkOAuthProvider |
| Address | CreateAddress, UpdateAddress, DeleteAddress, GetUserAddresses |
| Meter | CreateMeter, GetDashboardStats, GetConsumptionHistory, UploadMeterPhoto |
| Billing | CreateServiceProvider, CalculateTariffCost, GetExpenseDistribution |
| Export | ExportMeterReadings, ExportToCsv, ExportToPdf |

**Правила використання Actions:**
1. `handle()` — чиста бізнес-логіка, без HTTP-залежностей
2. Actions можуть викликати інші Actions для композиції
3. `rules()` — не використовуємо (Form Request виконує цю роль)

### Наслідки

**Позитивні:**
- Один клас = одна відповідальність
- Легке тестування (`CreateAddress::run(...)`)
- Легка композиція між Actions
- Можливість dispatch як Job

**Негативні:**
- Більше файлів порівняно з Service classes
- Потрібна дисципліна в іменуванні (verb + noun: CreateAddress)

---

## ADR-003: Repository Pattern

### Статус
Прийнято

### Контекст
Repository pattern застосовується в модулях, де потрібна абстракція доступу до даних та тестованість через моки. В Auth та Address модулях є явні Repository реалізації. Для простих CRUD операцій в інших модулях — Eloquent напряму через Actions.

### Рішення
Interface-based Repository в межах кожного модуля:

```
Modules/Auth/
├── Repositories/
│   ├── Contracts/
│   │   └── UserRepositoryInterface.php
│   └── UserRepository.php

Modules/Address/
├── Repositories/
│   ├── Contracts/
│   │   └── AddressRepositoryInterface.php
│   └── AddressRepository.php
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

**Binding у ServiceProvider:**
```php
$this->app->bind(AddressRepositoryInterface::class, AddressRepository::class);
```

**Правила:**
- Actions інжектують Repository interfaces, не конкретні класи
- Складні запити — в Repository, прості — в Eloquent scopes на моделях
- Repository повертає лише Models/Collections/Paginators (не Query Builder)

### Наслідки

**Позитивні:**
- Тестованість через interface binding
- Єдине місце для складних запитів
- Абстракція від Eloquent у шарі Actions

**Негативні:**
- Додатковий layer (interface + implementation)
- Потрібна дисципліна щоб не дублювати логіку між Repository та Eloquent scopes

---

## ADR-003a: DTO Pattern (Data Transfer Objects)

### Статус
Прийнято

### Контекст
Laravel не має формалізованого DTO layer для вхідних даних. Передача масивів між шарами знижує type safety та ускладнює рефакторинг.

### Рішення
Реалізуємо DTO як typed PHP класи з readonly properties для вхідних даних Actions.

**Структура:**
```
Modules/Address/
├── DTOs/
│   ├── CreateAddressData.php
│   ├── UpdateAddressData.php
│   └── AddressPaginationData.php
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

**Flow даних:**
```
HTTP Request → Form Request (validation) → DTO::fromRequest() → Action::handle(DTO) → Repository → Model
Model → Inertia::render($props)
```

**Правила:**
- DTO — завжди `final readonly class`
- PHP 8.x constructor promotion
- Factory method `fromRequest()` для конвертації з Form Request
- DTO не містять бізнес-логіки

### Наслідки

**Позитивні:**
- Type safety — Actions отримують typed objects замість масивів
- Self-documenting контракт між controller та action
- Refactoring safety (IDE підкаже при зміні полів)
- Immutability через readonly

**Негативні:**
- Додаткові класи (1 DTO на Create/Update операцію)
- Часткове дублювання полів між Form Request та DTO

---

## ADR-004: JWT автентифікація

### Статус
Замінено (ADR-012)

### Контекст
На початковому етапі проєкт реалізовував JWT автентифікацію (`php-open-source-saver/jwt-auth`) з refresh token flow для сумісності з .NET API клієнтами.

### Рішення (оригінальне)
Використовували `php-open-source-saver/jwt-auth` з custom `RefreshToken` моделлю.

- `JwtService` — генерація/валідація JWT
- `RefreshToken` model — зберігання refresh tokens в БД
- Claims: sub (user_id), email, name, role, jti
- Access token lifetime: 30 хв, refresh token: 7 днів

### Причина заміни
Після міграції на full-stack Inertia.js архітектуру REST API клієнти більше не потрібні. Сесійна автентифікація (ADR-012) є стандартним та безпечнішим підходом для web-додатків.

---

## ADR-005: OAuth через Laravel Socialite

### Статус
Прийнято

### Контекст
Потрібна OAuth інтеграція з Google та GitHub для реєстрації та входу користувачів.

### Рішення
Використовуємо `laravel/socialite` з web redirect flow (не stateless API flow).

**Web redirect flow:**
1. Користувач натискає "Увійти через Google"
2. `OAuthController@redirect` — редирект до OAuth провайдера
3. Провайдер повертає на `OAuthController@callback`
4. Socialite обробляє callback, отримує профіль користувача
5. Автоматичне створення або прив'язка існуючого акаунту
6. Створення сесії (не JWT)

**Actions:**
- `LinkOAuthProvider` — прив'язка провайдера до наявного акаунту
- `UnlinkOAuthProvider` — відв'язка провайдера

**Налаштування в Settings:**
- GET `/settings/oauth/{provider}/link` — ініціювати прив'язку
- DELETE `/settings/oauth/{provider}` — відв'язати провайдера

**Збережена бізнес-логіка:**
- Автоматичне створення користувачів при першому OAuth вході
- Авто-лінк до існуючого акаунту за email (якщо email збігається)
- Унікальна генерація username з OAuth профілю

### Наслідки
- Стандартний Laravel підхід, добре документований
- Легко додати нових провайдерів (Twitter, Apple тощо)
- Безпечний CSRF state через Socialite автоматично
- Не потребує статeless flow (на відміну від JWT варіанту)

---

## ADR-006: Обробка зображень через spatie/laravel-medialibrary

### Статус
Прийнято

### Контекст
Потрібне рішення для зберігання фото лічильників та аватарів з автоматичними конверсіями (optimized + thumbnail) та фоновою обробкою.

### Рішення
Використовуємо `spatie/laravel-medialibrary` — polymorphic media relation до Eloquent моделей.

**Чому spatie/laravel-medialibrary:**
- Автоматичні image conversions без ручного коду
- Polymorphic зв'язок — один підхід для всіх моделей
- Queued conversions через Laravel Queue (Redis)
- singleFile collection для аватарів (автозаміна)
- HEIC/HEIF підтримка через imagick driver

**Реалізація на моделях:**

```php
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

class User extends Authenticatable implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes([
                'image/jpeg', 'image/png', 'image/gif',
                'image/heic', 'image/heif',
            ]);
    }
}
```

**Параметри:**
- Optimized: 800px max width, JPEG 85%
- Thumbnail: 200px max width, JPEG 85%
- Avatar max upload: 2MB, formats: jpg, png, gif, heic, heif
- Meter reading max upload: 10MB, formats: jpg, png, webp

**Зміни в моделях:**
- `MeterReadingPhoto` та `MeterReadingImage` — не створюємо як окремі Eloquent моделі, їх замінює `media` таблиця (polymorphic)
- Поля avatar_* на User — замінені на media collection

### Наслідки

**Позитивні:**
- Значно менше custom коду (немає ImageService, ProcessMeterReadingPhoto job)
- Автоматичне управління файлами при видаленні моделі
- Polymorphic — один підхід для всіх медіа

**Негативні:**
- Додаткова `media` таблиця замість окремих photo таблиць
- Потрібен imagick в Docker для HEIC підтримки

---

## ADR-007: Експорт даних

### Статус
Прийнято

### Контекст
Потрібен експорт показників лічильників у CSV та PDF форматах.

### Рішення
- **CSV**: `league/csv` — RFC 4180 сумісний, proper escaping, streaming
- **PDF**: `spatie/laravel-pdf` — HTML-based PDF генерація

**Actions:**
- `ExportMeterReadings` — визначає формат і делегує
- `ExportToCsv` — генерація CSV через league/csv
- `ExportToPdf` — генерація PDF через spatie/laravel-pdf

**Фільтрація:** за датами та адресою користувача.

### Наслідки
- Стандартні Laravel пакети з активною підтримкою
- PDF layout базується на HTML/CSS (не pixel-perfect)
- Streaming для великих CSV без memory overhead

---

## ADR-008: Міграції та схема БД

### Статус
Прийнято

### Контекст
Проєкт потребує version-controlled схеми БД для відтворення середовища та запуску тестів з чистою базою.

### Рішення
Всі зміни структури БД — через Laravel міграції. Snake_case naming convention. Всі constraints, indexes, unique keys — в міграціях.

**Підхід:**
- Нова структура або зміна — новий файл міграції
- Seed data — Laravel seeders + factories
- Тести використовують `RefreshDatabase` trait
- Окремий `db-test` PostgreSQL сервіс для тестів (compose.yml)

### Наслідки
- Повна відтворюваність середовища
- Тести можуть використовувати RefreshDatabase без впливу на dev БД
- Паралельне тестування через окремий test DB instance

---

## ADR-009: API Versioning

### Статус
Замінено

### Контекст
Початково проєкт реалізовував REST API з URL-based versioning (`/api/v1/...`).

### Рішення (оригінальне)
Маршрути з prefix `/api/v1` в кожному модулі через `routes/api.php`.

### Причина заміни
Після міграції на Inertia.js + React SPA окремий REST API більше не потрібен. Всі маршрути — виключно web routes без версіонування. Маршрути в кожному модулі знаходяться в `routes/web.php`.

---

## ADR-010: Тестування з Pest 4

### Статус
Прийнято

### Контекст
Потрібна стратегія тестування для full-stack додатку з Inertia.js та модульною архітектурою.

### Рішення

**Структура тестів:**
```
tests/
├── Browser/                    ← Pest 4 browser tests (Inertia page rendering)
│   ├── AuthTest.php
│   ├── DashboardTest.php
│   ├── MetersTest.php
│   ├── ReadingsTest.php
│   ├── AddressesTest.php
│   ├── ProvidersTest.php
│   └── SettingsTest.php
├── Feature/
│   └── Web/                    ← HTTP feature tests
│       ├── Auth/
│       ├── Dashboard/
│       ├── Address/
│       ├── Meter/
│       ├── Billing/
│       ├── HealthCheckTest.php
│       └── SecurityHeadersTest.php
└── Pest.php

Modules/*/tests/
├── Feature/
└── Unit/
    └── Actions/                ← Unit tests for Actions
```

**phpunit.xml** включає `Modules/*/tests/*`.

**Кожен модуль має Pest.php:**
```php
uses(Tests\TestCase::class)->in('Feature');
uses(Tests\TestCase::class)->in('Unit');
```

**Підхід до тестування:**
- Browser tests: рендеринг Inertia сторінок, форми, навігація, `assertNoJavaScriptErrors()`
- Feature tests: HTTP endpoints (status codes, redirect, session, Inertia response)
- Unit tests: Actions (handle method напряму)

**Заборонено:**
- Unit tests для Eloquent моделей (відносини, CRUD)
- Тестування стандартного Eloquent функціоналу

### Наслідки
- Pest 4 повністю сумісний з модульною структурою
- Browser tests верифікують SPA поведінку через Inertia
- Кожен модуль тестується незалежно

---

## ADR-011: Міжмодульна комунікація

### Статус
Прийнято

### Контекст
Модулі потребують доступу до моделей/Actions інших модулів (наприклад, Meter потребує Address, Billing потребує UtilityType).

### Рішення

**Рівні зв'язності:**

1. **Shared Module** — спільні entities (UtilityType, Currency, ServiceCategory) для кількох модулів
2. **Read-only доступ** — модуль може читати Eloquent моделі іншого модуля
3. **Events** — для side effects (наприклад, після видалення Address — очистити пов'язані Meters)
4. **Actions** — модуль може викликати Actions іншого модуля

**Правила:**
- Ніколи не імпортувати Actions з іншого модуля напряму в Controller
- Моделі можна імпортувати cross-module (вони public)
- Складна cross-module логіка — через Events

**Граф залежностей:**
```
Shared ← Address ← Meter ← Export
                 ← Billing ←┘
Auth (ізольований, тільки User model використовується іншими)
```

### Наслідки
- Чіткі межі між модулями
- Shared module запобігає circular dependencies
- Events забезпечують loose coupling

---

## ADR-012: Сесійна автентифікація

### Статус
Прийнято

### Контекст
Після міграції на full-stack Inertia.js + React SPA JWT автентифікація стала надмірно ускладненою. Web-додаток не потребує stateless токенів — сесійна автентифікація є стандартним та безпечнішим підходом для browser-based SPA.

### Рішення
Використовуємо вбудовану Laravel сесійну автентифікацію:

- **Guard**: `web` (session driver)
- **Provider**: `users` (Eloquent, таблиця `users`)
- **Session driver**: `database` (таблиця `sessions`)
- **Session lifetime**: 120 хвилин
- **CSRF**: вбудований Laravel CSRF middleware

**config/auth.php:**
```php
'defaults' => [
    'guard' => 'web',
    'passwords' => 'users',
],

'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
],
```

**Видалено:**
- `php-open-source-saver/jwt-auth` пакет
- `config/jwt.php`
- `RefreshToken` модель та міграція
- `JwtService`
- `RefreshTokenRepository`
- `/api/v1/auth/refresh-token`, `/api/v1/auth/revoke-token` ендпоінти

**Actions автентифікації:**
- `LoginUser` — `Auth::attempt()` + `session()->regenerate()`
- `RegisterUser` — створення User + автоматичний вхід
- `LogoutUser` — `Auth::logout()` + session invalidation

**Middleware:**
- `auth` — захист всіх приватних маршрутів
- `guest` — редирект автентифікованих зі сторінок входу/реєстрації

### Чому не JWT для SPA

- SPA в одному домені з backend не потребує stateless tokens
- Сесії + CSRF дають кращий захист проти XSS (httpOnly cookie)
- Inertia.js автоматично передає CSRF token в кожному запиті
- Простіша реалізація без token refresh logic

### Наслідки

**Позитивні:**
- Менше коду (немає JwtService, RefreshToken, refresh flow)
- Стандартний Laravel підхід з повною документацією
- Безпечніший httpOnly session cookie
- Автоматичний CSRF захист через Inertia

**Негативні:**
- Неможливо використовувати з мобільними клієнтами або сторонніми API
- Прив'язка до браузерного клієнта

---

## ADR-013: Inertia.js v2 + React frontend архітектура

### Статус
Прийнято

### Контекст
Проєкт потребував frontend рішення. Варіанти:
1. REST API + окремий SPA (React/Next.js)
2. Blade templates + Alpine.js
3. Inertia.js + React (server-driven SPA)

### Рішення
Використовуємо `inertia-laravel/inertia` v2 + React 19 + TypeScript.

**Чому Inertia.js:**
- Один codebase — немає окремого API для frontend
- Server-side routing через Laravel (не React Router)
- Автентифікація, авторизація та валідація — на сервері
- SPA-like UX без складності окремого API
- Seamless передача props з Laravel до React

**Структура frontend:**
```
resources/js/
├── components/             ← shared UI components
│   ├── ui/                 ← base components (Button, Input, etc.)
│   └── ...
├── layouts/
│   ├── AuthenticatedLayout.tsx
│   └── GuestLayout.tsx
├── pages/
│   ├── Auth/
│   │   ├── Login.tsx
│   │   └── Register.tsx
│   ├── Dashboard/
│   │   └── Index.tsx
│   ├── Meters/
│   │   ├── Index.tsx
│   │   ├── Create.tsx
│   │   └── Edit.tsx
│   ├── Readings/
│   │   ├── Index.tsx
│   │   └── Create.tsx
│   ├── Addresses/
│   │   ├── Index.tsx
│   │   ├── Create.tsx
│   │   └── Edit.tsx
│   ├── Providers/
│   │   ├── Index.tsx
│   │   ├── Create.tsx
│   │   └── Edit.tsx
│   └── Settings/
│       └── Index.tsx
├── types/                  ← TypeScript interfaces
└── app.tsx                 ← Inertia bootstrap
```

**Технологічний стек frontend:**
- **React 19** — UI бібліотека
- **TypeScript** (strict mode) — типізація
- **Tailwind CSS v4** — стилізація
- **Tailwind Variants (`tv()`)** — variant-based компоненти
- **react-hook-form** — управління формами (поруч з `useForm` від Inertia)
- **Recharts** — графіки на Dashboard
- **Lucide React** — іконки
- **Ziggy** — типізовані Laravel маршрути у JS

**Правила TypeScript:**
- Компоненти — named exports (не default)
- Props — через `interface` (не `type`)
- Без `any` — `unknown` з type narrowing
- Функціональні компоненти (без class components)

**Приклад Inertia render з Laravel:**
```php
return Inertia::render('Meters/Index', [
    'meters' => MeterResource::collection($meters->paginate(15)),
    'addresses' => AddressResource::collection($addresses),
]);
```

**Приклад форми в React:**
```tsx
import { useForm } from '@inertiajs/react';

const { data, setData, post, errors, processing } = useForm({
    name: '',
    serial_number: '',
});

const submit = (e: React.FormEvent) => {
    e.preventDefault();
    post(route('meters.store'));
};
```

### Наслідки

**Позитивні:**
- Єдиний codebase без окремого API
- Server-side validation errors автоматично передаються в React
- Стандартна Laravel авторизація без JWT
- SPA UX (без page refresh) при мінімальній складності
- TypeScript типізація через shared interfaces

**Негативні:**
- Неможливо перевикористати backend як API для мобільних клієнтів без додаткового шару
- Менша гнучкість у deployment (frontend та backend завжди разом)
- Крива навчання Inertia.js для нових розробників
