# PRD: Komunalka API — Міграція з .NET на Laravel

## 1. Огляд проєкту

### 1.1 Мета

Повна міграція REST API для управління комунальними послугами з .NET 8 / Entity Framework Core на Laravel 12 / PHP 8.4 зі збереженням 100% функціональної сумісності, тієї самої схеми бази даних PostgreSQL та ідентичних API-контрактів.

### 1.2 Контекст

Існуюче API побудоване на ASP.NET Core 8 з Entity Framework Core, PostgreSQL, JWT-автентифікацією, OAuth (Google, GitHub), обробкою зображень та експортом даних. API обслуговує мобільний/веб додаток для обліку комунальних показників.

### 1.3 Критерії успіху

- Всі 60+ ендпоінтів відтворені з ідентичними URL, методами, параметрами
- Існуюча база даних PostgreSQL використовується без змін схеми
- Ідентичний формат JSON-відповідей (Laravel-сумісний формат пагінації вже використовується в .NET)
- JWT-автентифікація з refresh token flow
- OAuth інтеграція (Google, GitHub)
- Обробка зображень (оптимізація, thumbnails)
- Експорт даних (CSV, PDF)
- Тестове покриття Pest 4

---

## 2. Доменна модель

### 2.1 Bounded Contexts (модулі)

| Модуль | Опис | Entities |
|--------|------|----------|
| **Auth** | Автентифікація, авторизація, OAuth | User, RefreshToken |
| **Address** | Управління адресами | Address, AddressType, Region, UserAddress |
| **Meter** | Лічильники та показники | Meter, MeterReading, MeterReadingPhoto, MeterReadingImage |
| **Billing** | Постачальники, тарифи, розрахунки | ServiceProvider, Tariff, Currency, UtilityType |
| **Export** | Експорт даних | — (використовує моделі Meter/Billing) |
| **Shared** | Спільні компоненти | ServiceCategory, ServiceCounter, ServiceCounterMeasurement, ServiceCounterValue, AddressesServiceCategory |

### 2.2 Повний перелік entities (19)

#### Auth Module
1. **User** — id, username, first_name, last_name, phone_number, password, email (unique), role, auth_provider, external_id, email_verified, last_login_at, avatar_optimized_path, avatar_thumbnail_path, avatar_mime_type, avatar_size_in_bytes, avatar_width, avatar_height, created_at, updated_at
2. **RefreshToken** — id, token, expiry_date, is_used, is_revoked, created_at, user_id (FK)

#### Address Module
3. **Address** — id, region_id (FK), city, street, building_number, apartment_number, zip_code, notes, address_type_id (FK), created_at, updated_at, deleted_at (soft delete)
4. **AddressType** — id, name, description, icon, created_at, updated_at
5. **Region** — id, name, created_at, updated_at
6. **UserAddress** (pivot: address_user) — id, user_id (FK), address_id (FK), is_primary, created_at, updated_at

#### Meter Module
7. **Meter** — id, address_id (FK), utility_type_id (FK), serial_number, name, description, model_name, location, photo_path, installation_date, initial_reading, service_provider_id (FK nullable), notes, is_active, created_at, updated_at
8. **MeterReading** — id, meter_id (FK), reading_value, reading_date, previous_reading_value, consumption, notes, is_estimated, tariff_id (FK nullable), created_at, updated_at
9. **MeterReadingPhoto** — id, meter_reading_id (FK), optimized_path, thumbnail_path, optimized_size_in_bytes, thumbnail_size_in_bytes, width, height, mime_type, is_processed, created_at, updated_at
10. **MeterReadingImage** — id, service_counter_value_id (FK), optimized_path, thumbnail_path, optimized_size_in_bytes, thumbnail_size_in_bytes, width, height, mime_type, is_processed, created_at, updated_at

#### Billing Module
11. **ServiceProvider** — id, name, description, phone, email, website, address_id (FK), utility_type_id (FK), is_active, created_at, updated_at
12. **Tariff** — id, service_provider_id (FK), utility_type_id (FK), currency_id (FK), name, base_rate, service_fee, effective_from, effective_to, notes, created_at, updated_at
13. **Currency** — id, code, name, symbol, created_at, updated_at
14. **UtilityType** — id, slug, display_name, unit, description, is_active, created_at, updated_at

#### Shared Module
15. **ServiceCategory** — id, name, created_at, updated_at
16. **ServiceCounter** — id, address_id, service_category_id (FK), serial_number, service_counter_measurement_id (FK), created_at, updated_at
17. **ServiceCounterMeasurement** — id, name, measurement, created_at, updated_at
18. **ServiceCounterValue** — id, service_counter_id (FK), value, created_at, updated_at
19. **AddressesServiceCategory** (pivot: address_service_category) — id, address_id (FK), service_category_id (FK), created_at, updated_at

### 2.3 Ключові зв'язки

```
User (1) ←→ (M) UserAddress (M) ←→ (1) Address
User (1) → (M) RefreshToken

Address (M) → (1) Region
Address (M) → (1) AddressType
Address (1) → (M) ServiceProvider
Address (1) → (M) Meter
Address (1) → (M) ServiceCounter

ServiceProvider (1) → (M) Meter
ServiceProvider (1) → (M) Tariff
ServiceProvider (M) → (1) UtilityType
ServiceProvider (M) → (1) Address

Meter (M) → (1) UtilityType
Meter (M) → (1) ServiceProvider (nullable)
Meter (1) → (M) MeterReading

MeterReading (1) → (M) MeterReadingPhoto
MeterReading (M) → (1) Tariff (nullable)

Tariff (M) → (1) ServiceProvider
Tariff (M) → (1) UtilityType
Tariff (M) → (1) Currency

ServiceCounter (M) → (1) ServiceCategory
ServiceCounter (M) → (1) ServiceCounterMeasurement
ServiceCounter (1) → (M) ServiceCounterValue
ServiceCounterValue (1) → (M) MeterReadingImage
```

---

## 3. API Endpoints — повний перелік

### 3.1 Auth Module (10 ендпоінтів)

| Method | URL | Auth | Опис |
|--------|-----|------|------|
| POST | /api/v1/auth/register | - | Реєстрація нового користувача |
| POST | /api/v1/auth/login | - | Вхід (email + password) |
| POST | /api/v1/auth/refresh-token | - | Оновлення JWT токена |
| POST | /api/v1/auth/revoke-token | JWT | Відкликання refresh token |
| GET | /api/v1/auth/validate-token | JWT | Перевірка валідності JWT |
| POST | /api/v1/auth/oauth/login | - | Вхід через OAuth провайдер |
| GET | /api/v1/auth/oauth/{provider}/authorize | - | Отримання OAuth URL |
| POST | /api/v1/auth/oauth/callback | - | Обробка OAuth callback |
| POST | /api/v1/auth/oauth/link | JWT | Прив'язка OAuth до акаунту |
| DELETE | /api/v1/auth/oauth/unlink/{provider} | JWT | Відв'язка OAuth |

### 3.2 Users Module (7 ендпоінтів)

| Method | URL | Auth | Опис |
|--------|-----|------|------|
| GET | /api/v1/users | JWT | Список всіх користувачів |
| GET | /api/v1/users/{id} | JWT | Профіль користувача |
| POST | /api/v1/users | JWT | Створення користувача |
| PUT | /api/v1/users/{id} | JWT | Оновлення профілю (multipart) |
| DELETE | /api/v1/users/{id} | JWT | Видалення користувача |
| GET | /api/v1/users/{id}/avatar | - | Аватар (оптимізований) |
| GET | /api/v1/users/{id}/avatar/thumbnail | - | Аватар (thumbnail) |

### 3.3 Address Module (7 ендпоінтів)

| Method | URL | Auth | Опис |
|--------|-----|------|------|
| GET | /api/v1/address | JWT | Адреси поточного користувача (пагінація) |
| GET | /api/v1/address/{id} | JWT | Деталі адреси |
| POST | /api/v1/address | JWT | Створення адреси |
| PUT | /api/v1/address/{id} | JWT | Оновлення адреси |
| DELETE | /api/v1/address/{id} | JWT | Видалення адреси |
| GET | /api/v1/addresstype | JWT | Типи адрес |
| GET | /api/v1/addresstype/{id} | JWT | Тип адреси за ID |

### 3.4 Region Module (2 ендпоінти)

| Method | URL | Auth | Опис |
|--------|-----|------|------|
| GET | /api/v1/region | JWT | Список регіонів |
| GET | /api/v1/region/{id} | JWT | Регіон за ID |

### 3.5 Meter Module (8 ендпоінтів)

| Method | URL | Auth | Опис |
|--------|-----|------|------|
| GET | /api/v1/meter | JWT | Всі лічильники |
| GET | /api/v1/meter/{id} | JWT | Лічильник за ID |
| GET | /api/v1/meter/address/{addressId} | JWT | Лічильники за адресою |
| GET | /api/v1/meter/active | JWT | Активні лічильники |
| POST | /api/v1/meter | JWT | Створення лічильника (JSON) |
| POST | /api/v1/meter/{id}/photo | JWT | Завантаження фото лічильника |
| PUT | /api/v1/meter/{id} | JWT | Оновлення лічильника |
| DELETE | /api/v1/meter/{id} | JWT | Видалення лічильника |

### 3.6 Meter Reading Module (11 ендпоінтів)

| Method | URL | Auth | Опис |
|--------|-----|------|------|
| POST | /api/v1/meter-readings/batch | JWT | Пакетне створення показників (multipart) |
| GET | /api/v1/meter-readings/address/{addressId} | JWT | Показники за адресою (date range) |
| GET | /api/v1/meter-readings/{id} | JWT | Показник за ID |
| DELETE | /api/v1/meter-readings/{id} | JWT | Видалення показника |
| GET | /api/v1/meter-readings/photos/{id}/optimized | - | Фото показника (оптимізоване) |
| GET | /api/v1/meter-readings/photos/{id}/thumbnail | - | Фото показника (thumbnail) |
| POST | /api/v1/meterreading | JWT | Створення показника (legacy, multipart) |
| GET | /api/v1/meterreading/{id} | JWT | Показник за ID (legacy) |
| GET | /api/v1/meterreading/images/{id}/optimized | - | Зображення (legacy) |
| GET | /api/v1/meterreading/images/{id}/thumbnail | - | Thumbnail (legacy) |
| DELETE | /api/v1/meterreading/{id} | JWT | Видалення (legacy) |

### 3.7 Service Provider Module (6 ендпоінтів)

| Method | URL | Auth | Опис |
|--------|-----|------|------|
| GET | /api/v1/service-providers | JWT | Постачальники для адрес користувача |
| GET | /api/v1/service-providers/{id} | JWT | Постачальник за ID (з тарифами) |
| GET | /api/v1/service-providers/address/{addressId} | JWT | Постачальники за адресою |
| POST | /api/v1/service-providers | JWT | Створення постачальника (з тарифами) |
| PUT | /api/v1/service-providers/{id} | JWT | Оновлення постачальника |
| DELETE | /api/v1/service-providers/{id} | JWT | Видалення постачальника |

### 3.8 Utility Type Module (2 ендпоінти)

| Method | URL | Auth | Опис |
|--------|-----|------|------|
| GET | /api/v1/utility-types | - | Типи послуг (публічний) |
| GET | /api/v1/utility-types/{id} | - | Тип послуги за ID (публічний) |

### 3.9 Currency Module (5 ендпоінтів)

| Method | URL | Auth | Опис |
|--------|-----|------|------|
| GET | /api/v1/currency | - | Список валют (публічний) |
| GET | /api/v1/currency/{id} | - | Валюта за ID (публічний) |
| POST | /api/v1/currency | - | Створення валюти |
| PUT | /api/v1/currency/{id} | - | Оновлення валюти |
| DELETE | /api/v1/currency/{id} | - | Видалення валюти |

### 3.10 Export Module (1 ендпоінт)

| Method | URL | Auth | Опис |
|--------|-----|------|------|
| POST | /api/v1/export/meter-readings | JWT | Експорт показників (CSV/PDF) |

**Загалом: ~59 ендпоінтів**

---

## 4. Бізнес-логіка

### 4.1 Автентифікація

- **Локальна**: email + password (BCrypt), JWT токен (30 хв), refresh token (7 днів)
- **OAuth**: Google (ID token + auth code flow), GitHub (access token + auth code flow)
- **OAuth flow**: авто-створення користувача, авто-лінк для існуючих без паролю
- **Refresh**: валідація is_used, is_revoked, expiry_date
- **Security**: запобігання OAuth-юзерам входити через password, CSRF state для OAuth

### 4.2 Multi-tenancy через UserAddress

- Всі запити до даних фільтруються через user_id → UserAddress → Address
- ForbiddenResult якщо користувач не має доступу до адреси
- Один primary address на користувача (unique filtered index)

### 4.3 Пакетні показники лічильників

- Валідація: лічильник належить адресі, активний, reading >= previous
- Автоматичне визначення тарифу за датою (EffectiveFrom <= date <= EffectiveTo)
- Розрахунок: consumption = current - previous, cost = consumption × BaseRate + ServiceFee
- Асинхронна обробка фото (optimized 800px + thumbnail 200px, JPEG 85%)

### 4.4 Обробка зображень

- Формати: JPEG, PNG, WebP + HEIC/HEIF (через ImageMagick)
- EXIF: авто-орієнтація, видалення метаданих
- Два типи: meter reading photos (10MB max) та avatars (2MB max)
- Фонова обробка через Channel-based queue

### 4.5 Експорт

- CSV: RFC 4180, proper escaping
- PDF: A4 landscape, 8-column table (QuestPDF)
- Фільтрація за датами та адресами

### 4.6 Seed Data

- **Регіони**: 25 областей України
- **Типи адрес**: Квартира, Приватний будинок, Офіс
- **Типи послуг**: electricity, gas, cold-water, hot-water, heating, sewage

---

## 5. Формат відповідей API

### 5.1 Успішна відповідь

```json
{
  "data": { ... }
}
```

### 5.2 Пагінована відповідь

```json
{
  "data": [...],
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "path": "/api/v1/address",
    "per_page": 15,
    "to": 15,
    "total": 73
  }
}
```

### 5.3 Помилка

```json
{
  "statusCode": 404,
  "message": "Address not found",
  "details": null,
  "timestamp": "2025-01-01T00:00:00Z",
  "path": "/api/v1/address/999"
}
```

### 5.4 Помилка валідації

```json
{
  "statusCode": 422,
  "message": "Data validation error",
  "errors": {
    "email": ["The email field is required."],
    "password": ["Password must be at least 8 characters."]
  }
}
```

---

## 6. Нефункціональні вимоги

| Вимога | Деталі |
|--------|--------|
| **Runtime** | PHP 8.4, FrankenPHP/Octane |
| **Database** | PostgreSQL 17 (існуюча схема) |
| **Cache/Queue** | Redis |
| **Testing** | Pest 4, PHPUnit 12 |
| **Code style** | Laravel Pint |
| **Containers** | Docker Compose |
| **API versioning** | /api/v1/* |
| **Rate limiting** | Laravel built-in throttle |
| **Health checks** | /health, /health/ready |
| **CORS** | Configurable origins |
| **Logging** | Laravel Log (Serilog-equivalent structured logging) |

---

## 7. Залежності (Laravel-еквіваленти)

| .NET пакет | Laravel еквівалент |
|------------|-------------------|
| Entity Framework Core | Eloquent ORM (built-in) |
| System.IdentityModel.Tokens.Jwt | tymon/jwt-auth або Laravel Passport/Sanctum + custom JWT |
| Google.Apis.Auth | Laravel Socialite (Google) |
| HttpClient (GitHub) | Laravel Socialite (GitHub) |
| AutoMapper | Eloquent API Resources |
| Serilog | Laravel Log (Monolog) |
| AspNetCoreRateLimit | Laravel Rate Limiting (built-in) |
| SixLabors.ImageSharp | spatie/laravel-medialibrary (image conversions) |
| Magick.NET (HEIC) | spatie/laravel-medialibrary + imagick driver |
| QuestPDF | barryvdh/laravel-dompdf або spatie/laravel-pdf |
| BCrypt.Net | Laravel Hash (BCrypt, built-in) |
| DotNetEnv | Laravel .env (built-in) |
| Swagger/OpenAPI | knuckleswtf/scribe або l5-swagger |

---

## 8. Архітектурні патерни

### 8.1 Repository Pattern

Кожен модуль містить Repository layer з interface + Eloquent implementation:
- `Contracts/EntityRepositoryInterface.php` — контракт
- `EntityRepository.php` — Eloquent реалізація
- Base `RepositoryInterface` + `EloquentRepository` в `app/Repositories/`
- Actions інжектять Repositories через interface binding

### 8.2 DTO Pattern (Data Transfer Objects)

Typed readonly PHP classes для передачі даних між шарами:
- `final readonly class CreateAddressData` — input для Actions
- Factory method `fromRequest()` для конвертації з Form Request
- Response serialization — через Laravel API Resources (не DTO)

### 8.3 Media Management (spatie/laravel-medialibrary)

Замість окремих MeterReadingPhoto/MeterReadingImage моделей та custom ImageService:
- **MeterReading** — media collection `photos` (multiple), conversions: optimized (800px), thumbnail (200px)
- **User** — media collection `avatar` (singleFile), conversions: optimized, thumbnail
- Queued conversions через Laravel Queue (Redis)
- HEIC/HEIF підтримка через imagick

---

## 9. Out of Scope

- Зміна схеми бази даних (використовуємо існуючу)
- Нові фічі, яких немає в .NET API
- Frontend/mobile додатки
- CI/CD pipeline (окремий етап)
- Моніторинг та алертинг
