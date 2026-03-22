# PRD: Komunalka — Full-Stack веб-додаток для управління комунальними послугами

## 1. Огляд проєкту

### 1.1 Мета

Komunalka — повноцінний full-stack веб-додаток для обліку комунальних послуг: лічильників, показників споживання, постачальників, тарифів та адрес. Побудований на Laravel 13 + Inertia.js v2 + React 19 з серверним рендерингом сторінок через Inertia і сесійною автентифікацією.

### 1.2 Контекст

Додаток пройшов міграцію з REST API (Laravel + JWT) на full-stack SPA архітектуру з Inertia.js. Фронтенд більше не є окремим клієнтом — React-сторінки рендеряться безпосередньо через `Inertia::render()`, маршрути — виключно web, автентифікація — сесійна (database driver).

### 1.3 Критерії успіху

- Повний CRUD для адрес, лічильників, показників та постачальників послуг
- Сесійна автентифікація з підтримкою OAuth (Google, GitHub) через Laravel Socialite
- Dashboard зі статистикою споживання, розподілом витрат та останніми показниками
- Завантаження фото лічильників через spatie/laravel-medialibrary
- Експорт показників у CSV та PDF
- Тестове покриття: Browser tests + Feature tests + Unit tests (Pest 4)

---

## 2. Доменна модель

### 2.1 Bounded Contexts (модулі)

| Модуль | Опис | Entities |
|--------|------|----------|
| **Auth** | Автентифікація, OAuth, налаштування профілю | User |
| **Address** | Управління адресами користувача | Address, AddressType, Region, UserAddress |
| **Meter** | Лічильники та показники споживання | Meter, MeterReading |
| **Billing** | Постачальники послуг та тарифи | ServiceProvider, Tariff |
| **Export** | Генерація звітів | — (використовує моделі Meter/Billing) |
| **Shared** | Спільні довідники | UtilityType, Currency, ServiceCategory, ServiceCounter, ServiceCounterMeasurement, ServiceCounterValue, AddressServiceCategory |

### 2.2 Повний перелік entities (16)

#### Auth Module
1. **User** — id, username, first_name, last_name, phone_number, password, email (unique), role, auth_provider, external_id, email_verified, last_login_at, created_at, updated_at

#### Address Module
2. **Address** — id, region_id (FK), city, street, building_number, apartment_number, zip_code, notes, address_type_id (FK), created_at, updated_at, deleted_at (soft delete)
3. **AddressType** — id, name, description, icon, created_at, updated_at
4. **Region** — id, name, created_at, updated_at
5. **UserAddress** (pivot: address_user) — id, user_id (FK), address_id (FK), is_primary, created_at, updated_at

#### Meter Module
6. **Meter** — id, address_id (FK), utility_type_id (FK), serial_number, name, description, model_name, location, photo_path, installation_date, initial_reading, service_provider_id (FK nullable), notes, is_active, created_at, updated_at
7. **MeterReading** — id, meter_id (FK), reading_value, reading_date, previous_reading_value, consumption, notes, is_estimated, tariff_id (FK nullable), created_at, updated_at

#### Billing Module
8. **ServiceProvider** — id, name, description, phone, email, website, address_id (FK), utility_type_id (FK), is_active, created_at, updated_at
9. **Tariff** — id, service_provider_id (FK), utility_type_id (FK), currency_id (FK), name, base_rate, service_fee, effective_from, effective_to, notes, created_at, updated_at

#### Shared Module
10. **UtilityType** — id, slug, display_name, unit, description, is_active, created_at, updated_at
11. **Currency** — id, code, name, symbol, created_at, updated_at
12. **ServiceCategory** — id, name, created_at, updated_at
13. **ServiceCounter** — id, address_id (FK), service_category_id (FK), serial_number, service_counter_measurement_id (FK), created_at, updated_at
14. **ServiceCounterMeasurement** — id, name, measurement, created_at, updated_at
15. **ServiceCounterValue** — id, service_counter_id (FK), value, created_at, updated_at
16. **AddressServiceCategory** (pivot: address_service_category) — id, address_id (FK), service_category_id (FK), created_at, updated_at

### 2.3 Ключові зв'язки

```
User (1) ←→ (M) UserAddress (M) ←→ (1) Address

Address (M) → (1) Region
Address (M) → (1) AddressType
Address (1) → (M) ServiceProvider
Address (1) → (M) Meter
Address (1) → (M) ServiceCounter

ServiceProvider (1) → (M) Meter
ServiceProvider (1) → (M) Tariff
ServiceProvider (M) → (1) UtilityType

Meter (M) → (1) UtilityType
Meter (M) → (1) ServiceProvider (nullable)
Meter (1) → (M) MeterReading

MeterReading (M) → (1) Tariff (nullable)
MeterReading (1) → (M) Media (spatie/laravel-medialibrary, collection: photos)

Tariff (M) → (1) ServiceProvider
Tariff (M) → (1) UtilityType
Tariff (M) → (1) Currency

ServiceCounter (M) → (1) ServiceCategory
ServiceCounter (M) → (1) ServiceCounterMeasurement
ServiceCounter (1) → (M) ServiceCounterValue

User (1) → (M) Media (spatie/laravel-medialibrary, collection: avatar)
```

---

## 3. Web Routes — повний перелік

### 3.1 Загальні маршрути

| Method | URL | Middleware | Опис |
|--------|-----|------------|------|
| GET | /health | — | Health check |
| GET | /health/ready | — | Перевірка підключення до БД |
| GET | / | auth | Dashboard — головна сторінка |

### 3.2 Auth Module

**Гостьові маршрути (guest middleware):**

| Method | URL | Опис |
|--------|-----|------|
| GET | /login | Форма входу |
| POST | /login | Обробка входу |
| GET | /register | Форма реєстрації |
| POST | /register | Обробка реєстрації |
| GET | /auth/{provider}/redirect | Редирект до OAuth провайдера |
| GET | /auth/{provider}/callback | Обробка OAuth callback |

**Автентифіковані маршрути (auth middleware):**

| Method | URL | Опис |
|--------|-----|------|
| POST | /logout | Вихід із системи |
| GET | /settings | Сторінка налаштувань (вкладки) |
| PUT | /settings/profile | Оновлення профілю |
| PUT | /settings/password | Зміна паролю |
| DELETE | /settings/account | Видалення акаунту |
| GET | /settings/oauth/{provider}/link | Прив'язка OAuth провайдера |
| DELETE | /settings/oauth/{provider} | Відв'язка OAuth провайдера |

### 3.3 Address Module (auth middleware)

| Method | URL | Опис |
|--------|-----|------|
| GET | /addresses | Список адрес |
| GET | /addresses/create | Форма створення адреси |
| POST | /addresses | Збереження нової адреси |
| GET | /addresses/{address}/edit | Форма редагування |
| PUT/PATCH | /addresses/{address} | Оновлення адреси |
| DELETE | /addresses/{address} | Видалення адреси |

### 3.4 Meter Module (auth middleware)

| Method | URL | Опис |
|--------|-----|------|
| GET | /meters | Список лічильників |
| GET | /meters/create | Форма створення лічильника |
| POST | /meters | Збереження нового лічильника |
| GET | /meters/{meter}/edit | Форма редагування |
| PUT/PATCH | /meters/{meter} | Оновлення лічильника |
| DELETE | /meters/{meter} | Видалення лічильника |
| POST | /meters/{meter}/photo | Завантаження фото лічильника |
| GET | /readings | Список показників |
| GET | /readings/create | Форма внесення показника |
| POST | /readings | Збереження показника |
| DELETE | /readings/{reading} | Видалення показника |

### 3.5 Billing Module (auth middleware)

| Method | URL | Опис |
|--------|-----|------|
| GET | /providers | Список постачальників |
| GET | /providers/create | Форма створення |
| POST | /providers | Збереження постачальника |
| GET | /providers/{provider}/edit | Форма редагування |
| PUT/PATCH | /providers/{provider} | Оновлення постачальника |
| DELETE | /providers/{provider} | Видалення постачальника |

---

## 4. Бізнес-логіка

### 4.1 Автентифікація

- **Локальна**: email + password (BCrypt), сесія через database driver (lifetime 120 хв)
- **OAuth**: Google та GitHub через Laravel Socialite — web redirect flow
- **OAuth flow**: редирект до провайдера → callback → автоматичне створення або прив'язка існуючого акаунту → сесія
- **Settings**: прив'язка/відв'язка OAuth провайдерів для наявного акаунту

### 4.2 Multi-tenancy через UserAddress

- Всі дані (адреси, лічильники, показники, постачальники) фільтруються через user_id → UserAddress → Address
- Один primary address на користувача (unique filtered index)
- Перевірка належності ресурсів при кожному запиті

### 4.3 Dashboard статистика

- Загальна статистика: кількість лічильників, остання дата показників, споживання за місяць
- Графік споживання (CompactionChart) — за допомогою Recharts
- Розподіл витрат (ExpenseDistribution) — за типами послуг
- Остання таблиця показників (RecentReadingsTable)
- Швидкі дії (QuickActions)

### 4.4 Показники лічильників

- Валідація: лічильник належить адресі користувача, є активним, нове значення >= попереднього
- Автоматичне визначення тарифу за датою (effective_from <= date <= effective_to)
- Розрахунок: consumption = current - previous, cost = consumption × base_rate + service_fee

### 4.5 Обробка зображень

- Фото лічильників і аватари — через spatie/laravel-medialibrary
- Конверсії: optimized (800px, JPEG 85%) та thumbnail (200px, JPEG 85%)
- Фонова обробка через Laravel Queue (Redis)
- HEIC/HEIF підтримка через imagick

### 4.6 Експорт

- **CSV**: league/csv, RFC 4180
- **PDF**: spatie/laravel-pdf
- Фільтрація за датами та адресами

### 4.7 Seed Data

- **Регіони**: 25 областей України
- **Типи адрес**: Квартира, Приватний будинок, Офіс
- **Типи послуг**: electricity, gas, cold-water, hot-water, heating, sewage

---

## 5. Inertia Rendering

### 5.1 Принцип роботи

Додаток використовує Inertia.js v2 як міст між Laravel (backend) та React (frontend). Сервер повертає не JSON API, а Inertia-відповіді, які React рендерить як SPA.

```php
// Приклад Action як Inertia-контролер
return Inertia::render('Meters/Index', [
    'meters' => MeterResource::collection($meters),
    'filters' => $request->only(['search', 'address_id']),
]);
```

### 5.2 Структура сторінок (React TSX)

| Модуль | Сторінки |
|--------|---------|
| **Auth** | Login.tsx, Register.tsx |
| **Dashboard** | Index.tsx (ConsumptionChart, ExpenseDistribution, RecentReadingsTable, QuickActions) |
| **Meters** | Index.tsx, Create.tsx, Edit.tsx |
| **Readings** | Index.tsx, Create.tsx |
| **Addresses** | Index.tsx, Create.tsx, Edit.tsx |
| **Providers** | Index.tsx, Create.tsx, Edit.tsx |
| **Settings** | Index.tsx (ProfileTab, SecurityTab, AppearanceTab, AccountTab) |
| **Layouts** | AuthenticatedLayout.tsx, GuestLayout.tsx |

### 5.3 Форми

Всі форми використовують `useForm` з `@inertiajs/react` — автоматична обробка помилок валідації, стан завантаження, CSRF-захист.

### 5.4 Навігація

Маршрути генеруються на фронтенді через Ziggy (`tightenco/ziggy`) — типізовані URL без хардкоду рядків.

---

## 6. Нефункціональні вимоги

| Вимога | Деталі |
|--------|--------|
| **Runtime** | PHP 8.2+, Laravel Octane + FrankenPHP |
| **Frontend** | React 19, TypeScript (strict mode), Inertia.js v2 |
| **Стилізація** | Tailwind CSS v4, Tailwind Variants (`tv()`) |
| **Збірка** | Vite 8 |
| **Database** | PostgreSQL 17 |
| **Cache/Queue** | Redis 7.2+ |
| **Testing** | Pest 4 (Browser, Feature, Unit) |
| **Code style** | Laravel Pint, PHPStan level 7, Rector |
| **Containers** | Docker Compose |
| **Health checks** | /health, /health/ready |
| **Real-time** | Laravel Reverb (WebSockets) |
| **Logging** | Laravel Pail (structured log viewer) |

---

## 7. Залежності

### PHP (composer.json)

| Пакет | Призначення |
|-------|-------------|
| `laravel/framework: ^13.0` | Framework |
| `inertiajs/inertia-laravel: ^2.0` | Inertia.js server-side adapter |
| `laravel/octane: ^2.13` | High-performance application server |
| `laravel/reverb: ^1.7` | WebSockets (real-time) |
| `laravel/socialite: ^5.24` | OAuth (Google, GitHub) |
| `lorisleiva/laravel-actions: ^2.9` | Actions pattern |
| `nwidart/laravel-modules: ^12.0` | Модульна архітектура |
| `spatie/laravel-medialibrary: ^11.20` | Управління медіа-файлами |
| `spatie/laravel-pdf: ^2.2` | Генерація PDF |
| `league/csv: ^9.28` | Генерація CSV |
| `tightenco/ziggy: ^2.6` | Маршрути Laravel для JS |
| `laravel/tinker: ^3.0` | REPL для розробки |

### PHP dev (composer.json)

| Пакет | Призначення |
|-------|-------------|
| `pestphp/pest: ^4.4` | Тестовий фреймворк |
| `pestphp/pest-plugin-laravel: ^4.0` | Laravel інтеграція для Pest |
| `larastan/larastan: ^3.9` | PHPStan для Laravel |
| `laravel/pint: ^1.24` | Форматування коду |
| `laravel/boost: ^2.3` | Dev utilities |
| `laravel/pail: ^1.2.2` | Log viewer |

### JavaScript (package.json)

| Пакет | Призначення |
|-------|-------------|
| `@inertiajs/react: ^2.3.18` | Inertia.js React adapter |
| `react: ^19.2.4` | UI бібліотека |
| `react-hook-form: ^7.72.0` | Управління формами |
| `recharts: ^3.8.0` | Графіки та чарти |
| `lucide-react: ^0.577.0` | Іконки |
| `tailwind-variants: ^3.2.2` | Variant-based стилізація |
| `tailwind-merge: ^3.5.0` | Об'єднання Tailwind класів |
| `tailwindcss: ^4.2.2` | CSS framework |
| `typescript: ~5.9.3` | TypeScript |
| `vite: ^8.0.1` | Збірка фронтенду |

---

## 8. Архітектурні патерни

### 8.1 Actions (lorisleiva/laravel-actions)

Вся бізнес-логіка реалізована через Action класи — один клас, один бізнес-процес (~50+ Actions):

```
Auth: LoginUser, RegisterUser, CreateUser, UpdateUser, DeleteUser,
      GetUser, GetAllUsers, GetUserAvatar, LinkOAuthProvider, UnlinkOAuthProvider

Address: CreateAddress, UpdateAddress, DeleteAddress, PatchAddress,
         GetUserAddresses, GetUserAddress, GetAllRegions, GetRegion,
         GetAllAddressTypes, GetAddressType

Meter: CreateMeter, UpdateMeter, DeleteMeter, GetMeter, GetAllMeters,
       GetActiveMeters, GetMetersByAddress, CreateBatchReadings,
       DeleteMeterReading, GetMeterReading, GetReadingsByAddress,
       GetConsumptionHistory, GetRecentReadings, GetDashboardStats,
       UploadMeterPhoto, CreateServiceCounterValue, DeleteServiceCounterValue

Billing: CreateServiceProvider, UpdateServiceProvider, DeleteServiceProvider,
         GetServiceProvider, GetUserServiceProviders, GetServiceProvidersByAddress,
         CalculateTariffCost, GetEffectiveTariff, GetExpenseDistribution

Shared: CreateCurrency, UpdateCurrency, DeleteCurrency, GetCurrency,
        GetAllCurrencies, GetUtilityType, GetActiveUtilityTypes

Export: ExportMeterReadings, ExportToCsv, ExportToPdf
```

### 8.2 Repository Pattern

Використовується в модулях Auth та Address для абстракції доступу до даних:

```
Modules/Auth/Repositories/
├── Contracts/UserRepositoryInterface.php
└── UserRepository.php

Modules/Address/Repositories/
├── Contracts/AddressRepositoryInterface.php
└── AddressRepository.php
```

### 8.3 DTO Pattern

Typed readonly PHP класи для передачі структурованих даних між шарами:

```php
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
            // ...
        );
    }
}
```

### 8.4 Потік даних

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
React TSX Component (SPA rendering)
```

### 8.5 Media Management (spatie/laravel-medialibrary)

Фото лічильників та аватари зберігаються через polymorphic media relation:

- **MeterReading** — media collection `photos` (multiple files), конверсії: optimized (800px), thumbnail (200px)
- **User** — media collection `avatar` (singleFile), конверсії: optimized, thumbnail
- Queued conversions через Laravel Queue (Redis)

---

## 9. Out of Scope

- Мобільні клієнти (REST API)
- Публічний API (ендпоінти /api/*)
- CI/CD pipeline (окремий процес)
- Моніторинг та алертинг
