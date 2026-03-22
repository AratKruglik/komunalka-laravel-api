# Merge Plan: React Frontend → Laravel Inertia.js Monolith

## 1. Огляд

**Мета**: Об'єднати два окремих проєкти (Laravel API + React SPA) в єдиний Inertia.js монолітний застосунок.

**Поточний стан**:

| | Laravel API (`komunalka-laravel-api`) | React SPA (`komunalka-react-web`) |
|---|---|---|
| Framework | Laravel 13 + Octane (FrankenPHP) | React 19 + Vite 7 |
| Auth | JWT (`php-open-source-saver/jwt-auth` v2.8) | JWT токени в cookies + refresh logic |
| Routing | API routes `/api/v1/*` | React Router v7 (16 routes) |
| State | — | Context + useReducer (Auth, Address, Theme) |
| Styling | — | Tailwind CSS v4 (custom theme, dark mode) |
| Forms | 22 Form Requests | react-hook-form |
| Icons | — | lucide-react |
| Charts | — | recharts |
| DB | PostgreSQL 17, Redis 7.2 | — |
| Architecture | 6 модулів, 61 Action, 17 моделей | 7 модулів, 40+ компонентів, 30+ hooks |
| TypeScript | — | ~31,400 рядків |
| Package manager | Composer | pnpm |

**Цільовий стан**: Єдиний Laravel 13 проєкт з Inertia.js (React adapter), session-based auth, Vite 8, pnpm.

---

## 2. Архітектурні рішення

### 2.1 Авторизація: JWT → Session Auth (web guard)

| Аспект | JWT (поточний) | Session auth (цільовий) |
|--------|---------------|------------------------|
| Механізм | Access + Refresh tokens | Серверна сесія + CSRF |
| Пакет | `php-open-source-saver/jwt-auth` | Вбудований Laravel (без додаткових пакетів) |
| User model | Implements `JWTSubject` | Стандартний `Authenticatable` |
| Guard | `api` (JWT driver) | `web` (session driver) |
| OAuth | Stateless callback + token generation | Стандартний Socialite redirect flow |
| Token refresh | JwtService + RefreshToken model | Не потрібно (сесія автоматично) |
| Frontend | Axios interceptors, refresh timer | Inertia автоматично (cookies-based session) |

**Що видаляється**:
- `php-open-source-saver/jwt-auth` (composer package)
- `config/jwt.php`
- `Modules/Auth/Services/JwtService.php`
- `Modules/Auth/Models/RefreshToken.php`
- `Modules/Auth/Actions/RefreshUserToken.php`
- `Modules/Auth/Actions/RevokeToken.php`
- `Modules/Auth/Actions/ValidateToken.php`
- `Modules/Auth/DTOs/RefreshTokenData.php` (якщо є)
- `Modules/Auth/Http/Requests/RefreshTokenRequest.php`
- `JWTSubject` interface та методи `getJWTIdentifier()`, `getJWTCustomClaims()` з User model
- JWT exception handling з `bootstrap/app.php`

**Що модифікується**:
- `config/auth.php` — default guard: `web`, видалити `api` JWT guard
- `Modules/Auth/Actions/LoginUser.php` — повернути User замість token pair
- `Modules/Auth/Actions/RegisterUser.php` — аналогічно
- `Modules/Auth/Actions/OAuthLogin.php` / `HandleOAuthCallback.php` — Auth::login() замість JwtService
- `Modules/Auth/Models/User.php` — видалити `implements JWTSubject`

**Що залишається без змін**:
- OAuth через Socialite (Google, GitHub)
- Усі Form Requests (валідація)
- Business logic в Actions

### 2.2 API Routes → Видалення

API routes (`/api/v1/*`) повністю видаляються. Усі endpoints переходять на Inertia web routes. Якщо мобільний додаток буде потрібен у майбутньому — API буде додано окремо.

**Що видаляється**:
- `Modules/*/routes/api.php` (усі 6 модулів)
- `Modules/*/Http/Controllers/*Controller.php` (API контролери)
- `config/cors.php` (CORS більше не потрібен)
- `dedoc/scramble` (OpenAPI docs для API)
- `config/scramble.php`

### 2.3 Dashboard: Реальні дані

Dashboard в React використовує mock дані з `src/shared/data/`. В рамках міграції створюємо реальні Actions для агрегації:
- `GetDashboardStats` — загальна статистика (кількість адрес, лічильників, останні показники)
- `GetConsumptionHistory` — дані для графіка споживання за період
- `GetExpenseDistribution` — розподіл витрат за типами послуг
- `GetRecentReadings` — останні N показників по всіх лічильниках

### 2.4 Build: Vite 8

Фронтенд збирається через Vite 8 з `laravel-vite-plugin`. React проєкт зараз на Vite 7 — оновлюємо до v8.

### 2.5 camelCase ↔ snake_case

React проєкт використовує Axios interceptors для автоматичної конвертації. З Inertia дані приходять напряму з Laravel. Рішення: використовувати існуючі API Resources для форматування Inertia props — вони вже повертають коректний формат.

---

## 3. Що НЕ змінюється (DDD + Actions = священне)

> **КРИТИЧНО для агентів**: Міграція торкається ТІЛЬКИ transport layer (контролери, auth, routing, frontend). Бізнес-логіка, доменна архітектура та модульна структура залишаються БЕЗ ЗМІН.

### 3.1 Actions (`lorisleiva/laravel-actions`) — залишаються повністю

Усі 61 Action class залишаються як є. Вони є єдиним місцем бізнес-логіки в проєкті.

**Правило**: Web контролери ПОВИННІ викликати Actions, а не містити бізнес-логіку.

```php
// ПРАВИЛЬНО — контролер делегує Action:
public function store(StoreAddressRequest $request): RedirectResponse
{
    CreateAddress::run($request->user(), CreateAddressData::fromRequest($request));
    return redirect()->route('addresses.index')->with('success', 'Адресу створено');
}

// НЕПРАВИЛЬНО — логіка в контролері:
public function store(StoreAddressRequest $request): RedirectResponse
{
    $address = Address::create($request->validated()); // НІ!
    return redirect()->route('addresses.index');
}
```

**Actions з модифікаціями** (тільки 2 з 61):

| Action | Зміна | Причина |
|--------|-------|---------|
| `LoginUser` | Повернути `User` замість JWT token pair | JWT видаляється, контролер робить `Auth::login($user)` |
| `RegisterUser` | Повернути `User` замість JWT token pair | Аналогічно |

**Решта 59 Actions** — ZERO CHANGES. Вони незалежні від auth transport.

**4 нових Actions** (для Dashboard):
- `GetDashboardStats` — агрегація статистики користувача
- `GetConsumptionHistory` — дані для графіка споживання
- `GetExpenseDistribution` — розподіл витрат по типах послуг
- `GetRecentReadings` — останні показники лічильників

Нові Actions створюються в існуючих модулях за DDD-принципами.

### 3.2 Модульна структура (nwidart/laravel-modules) — залишається

6 доменних модулів залишаються без змін:

```
Modules/
├── Auth/          # Користувачі, авторизація, OAuth
├── Address/       # Адреси, регіони, типи адрес
├── Meter/         # Лічильники, показники, фото
├── Billing/       # Провайдери послуг, тарифи
├── Shared/        # Валюти, типи послуг, категорії
└── Export/        # Експорт CSV/PDF
```

Кожен модуль зберігає внутрішню структуру:
- `Actions/` — бізнес-логіка (НЕ ЧІПАТИ)
- `Models/` — Eloquent моделі (НЕ ЧІПАТИ)
- `DTOs/` — Data Transfer Objects (НЕ ЧІПАТИ)
- `Enums/` — Enum classes (НЕ ЧІПАТИ)
- `Policies/` — Authorization policies (НЕ ЧІПАТИ)
- `Database/migrations/` — міграції (НЕ ЧІПАТИ)
- `Database/factories/` — фабрики (НЕ ЧІПАТИ)
- `Http/Requests/` — Form Requests (НЕ ЧІПАТИ, перевикористовуються в Web контролерах)

**Що змінюється в модулях**:
- `Http/Controllers/` — API контролери замінюються Web контролерами **в тому самому модулі**
- `Http/Resources/` — API Resources (залишити якщо використовуються для Inertia props форматування)
- `routes/api.php` — видаляється, замінюється на `routes/web.php` **в тому самому модулі**

**Модульна структура контролерів** (кожен модуль має власні Web контролери):
```
Modules/Auth/Http/Controllers/Web/LoginController.php
Modules/Auth/Http/Controllers/Web/RegisterController.php
Modules/Auth/Http/Controllers/Web/OAuthController.php
Modules/Auth/Http/Controllers/Web/LogoutController.php
Modules/Address/Http/Controllers/Web/AddressController.php
Modules/Meter/Http/Controllers/Web/MeterController.php
Modules/Meter/Http/Controllers/Web/ReadingController.php
Modules/Billing/Http/Controllers/Web/ServiceProviderController.php
```

**НЕ виносити контролери в `app/Http/Controllers/`** — це порушує модульну архітектуру.

### 3.3 Моделі та Relationships — залишаються

17 Eloquent моделей залишаються без змін (окрім `User` — видалення `JWTSubject`).

Relationships, scopes, casts, soft deletes, MediaLibrary — все працює як і раніше.

### 3.4 Form Requests — перевикористовуються

22 Form Requests залишаються і використовуються в нових Web контролерах:

```php
// Web Controller використовує ТОЙ САМИЙ Form Request що і API Controller:
public function store(StoreAddressRequest $request): RedirectResponse
{
    // StoreAddressRequest з Modules/Address/Http/Requests/ — без змін
    CreateAddress::run(...);
}
```

### 3.5 Підсумок: що чіпаємо, що ні

| Шар | Статус | Деталі |
|-----|--------|--------|
| **Actions** | НЕ ЧІПАТИ (2 з 61 мінімально модифікуються) | Ядро бізнес-логіки |
| **Models** | НЕ ЧІПАТИ (1 з 17 — User: видалити JWTSubject) | Доменні сутності |
| **DTOs** | НЕ ЧІПАТИ | Типізовані дані |
| **Enums** | НЕ ЧІПАТИ | Value objects |
| **Policies** | НЕ ЧІПАТИ | Авторизація |
| **Form Requests** | НЕ ЧІПАТИ (перевикористовуються) | Валідація |
| **Migrations** | НЕ ЧІПАТИ | Схема БД |
| **Factories** | НЕ ЧІПАТИ | Тестові дані |
| **API Controllers** | ЗАМІНИТИ | На Web контролери **в тому самому модулі** (`Modules/*/Http/Controllers/Web/`) |
| **API Routes** | ВИДАЛИТИ | Замінити Web routes |
| **JWT Auth** | ВИДАЛИТИ | Замінити Session auth |
| **API Resources** | ОЦІНИТИ | Залишити якщо корисні для Inertia props |

---

## 4. Фази реалізації

### Фаза 1: Foundation Setup (2-3 дні)

#### 1.1 Composer зміни

**Додати**:
```bash
composer require inertiajs/inertia-laravel tightenco/ziggy
```

**Видалити**:
```bash
composer remove php-open-source-saver/jwt-auth dedoc/scramble
```

#### 1.2 pnpm + frontend залежності

**Ініціалізація** (package.json не існує в Laravel проєкті):

```bash
pnpm init
```

**Production залежності**:
```bash
pnpm add @inertiajs/react react@^19 react-dom@^19 react-hook-form@^7 recharts@^3 lucide-react tailwind-merge@^3 tailwind-variants@^3
```

**Dev залежності**:
```bash
pnpm add -D vite@^8 @vitejs/plugin-react laravel-vite-plugin typescript@~5.9 @types/react @types/react-dom tailwindcss@^4 @tailwindcss/vite
```

**Видалити** (з React проєкту, не переносити):
```
axios                    # Замінюється Inertia data flow
react-router             # Замінюється Inertia routing
```

#### 1.3 Vite 8 конфігурація

**Створити** `vite.config.ts`:
```typescript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
            '@shared': '/resources/js/shared',
            '@modules': '/resources/js/modules',
        },
    },
});
```

#### 1.4 TypeScript конфігурація

**Створити** `tsconfig.json`:
```json
{
    "compilerOptions": {
        "target": "ES2022",
        "module": "ESNext",
        "moduleResolution": "bundler",
        "jsx": "react-jsx",
        "strict": true,
        "noUnusedLocals": true,
        "noUnusedParameters": true,
        "paths": {
            "@/*": ["./resources/js/*"],
            "@shared/*": ["./resources/js/shared/*"],
            "@modules/*": ["./resources/js/modules/*"]
        },
        "types": ["vite/client"]
    },
    "include": ["resources/js/**/*"]
}
```

#### 1.5 Inertia root template

**Створити** `resources/views/app.blade.php`:
```blade
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Komunalka</title>
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.tsx'])
    @inertiaHead
    @routes
</head>
<body>
    @inertia
</body>
</html>
```

#### 1.6 Inertia entry point

**Створити** `resources/js/app.tsx`:
```tsx
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

createInertiaApp({
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.tsx`,
            import.meta.glob('./Pages/**/*.tsx'),
        ),
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
```

#### 1.7 HandleInertiaRequests middleware

**Створити** `app/Http/Middleware/HandleInertiaRequests.php`:

Shared data:
- `auth.user` — поточний користувач (UserResource або null)
- `auth.user.addresses` — адреси користувача
- `flash.success` / `flash.error` — flash повідомлення

**Зареєструвати** в `bootstrap/app.php` → web middleware group.

#### 1.8 CSS Theme міграція

**Скопіювати** `komunalka-react-web/src/index.css` → `resources/css/app.css`

Містить:
- Tailwind v4 `@theme` директиву
- Custom colors (gold primary, purple secondary)
- Dark mode змінні (`[data-theme=dark]`)
- Montserrat шрифт
- Spacing, shadows, typography scale

#### 1.9 Auth guard перемикання

**Модифікувати** `config/auth.php`:
```php
'defaults' => [
    'guard' => 'web',        // було: 'api'
    'passwords' => 'users',
],

'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    // Видалити 'api' JWT guard
],
```

---

### Фаза 2: Auth Migration (2-3 дні)

#### 2.1 Web контролери авторизації

**Створити** `Modules/Auth/Http/Controllers/Web/`:

| Контролер | Методи | Опис |
|-----------|--------|------|
| `LoginController` | `show()`, `store()` | Inertia::render('Auth/Login'), Auth::login() |
| `RegisterController` | `show()`, `store()` | Inertia::render('Auth/Register'), Auth::login() |
| `OAuthController` | `redirect()`, `callback()` | Socialite redirect (не stateless), Auth::login() |
| `LogoutController` | `destroy()` | Auth::logout(), session invalidate |

**Патерн контролера** (приклад LoginController):
```php
public function show(): \Inertia\Response
{
    return Inertia::render('Auth/Login');
}

public function store(LoginRequest $request): RedirectResponse
{
    $user = LoginUser::run($request->validated());
    Auth::login($user, $request->boolean('remember'));

    return redirect()->intended(route('dashboard'));
}
```

#### 2.2 Web routes

**Додати в** `routes/web.php`:
```php
// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
    Route::get('/auth/{provider}/redirect', [OAuthController::class, 'redirect']);
    Route::get('/auth/{provider}/callback', [OAuthController::class, 'callback']);
});

// Auth routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LogoutController::class, 'destroy'])->name('logout');
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    // ... інші routes додаються у фазі 4
});
```

#### 2.3 Модифікація LoginUser Action

**Поточний стан**: Генерує JWT token pair через JwtService.
**Цільовий стан**: Валідує credentials, повертає User.

```php
// Було: return JwtService::generateTokenPair($user);
// Стало: return $user;
```

Аналогічно для RegisterUser, OAuthLogin, HandleOAuthCallback.

#### 2.4 OAuth flow зміна

**Поточний стан** (stateless API):
1. React → GET `/api/v1/auth/oauth/{provider}/authorize` → отримує URL
2. React → redirect до provider
3. Provider → redirect до React `/auth/callback`
4. React → POST `/api/v1/auth/oauth/callback` з code → отримує JWT

**Цільовий стан** (server-side):
1. Browser → GET `/auth/{provider}/redirect` → Laravel redirect до provider
2. Provider → GET `/auth/{provider}/callback` → Laravel обробляє, Auth::login(), redirect до dashboard

#### 2.5 JWT cleanup

Видалити всі JWT-related файли (перелік у розділі 2.1 цього документа).

Модифікувати `bootstrap/app.php` — видалити JWT exception handlers:
```php
// Видалити:
// TokenExpiredException → 401
// TokenInvalidException → 401
// JWTException → 401
```

#### 2.6 Auth pages міграція

**Скопіювати та адаптувати**:

| React файл | Inertia файл | Зміни |
|------------|-------------|-------|
| `modules/auth/pages/LoginPage.tsx` | `resources/js/Pages/Auth/Login.tsx` | react-router Link → Inertia Link; useNavigate → router.visit; authService.login → Inertia useForm + POST |
| `modules/auth/pages/RegisterPage.tsx` | `resources/js/Pages/Auth/Register.tsx` | Аналогічно |
| `modules/auth/pages/OAuthCallbackPage.tsx` | Видалити | Callback обробляється на сервері |

---

### Фаза 3: UI Components Migration (1-2 дні)

#### 3.1 Структура файлів

```
resources/js/
├── app.tsx                          # Inertia entry
├── types/                           # З shared/types/
│   ├── entities.ts
│   ├── auth.ts
│   └── index.ts
├── Components/
│   ├── ui/                          # З shared/components/ui/ (AS-IS)
│   │   ├── Button.tsx
│   │   ├── Card.tsx
│   │   ├── Input.tsx
│   │   ├── PasswordInput.tsx
│   │   ├── Select.tsx
│   │   ├── Textarea.tsx
│   │   ├── Checkbox.tsx
│   │   ├── RadioCard.tsx
│   │   ├── Label.tsx
│   │   ├── FormMessage.tsx
│   │   ├── PhotoDropzone.tsx
│   │   ├── Alert.tsx
│   │   ├── Badge.tsx
│   │   ├── ConfirmDialog.tsx
│   │   ├── DropdownMenu.tsx
│   │   ├── Spinner.tsx
│   │   └── UserAvatar.tsx
│   ├── navigation/                  # З shared/components/navigation/
│   │   ├── AuthenticatedSidebar.tsx  # Link → Inertia Link
│   │   ├── AuthenticatedTopbar.tsx   # useUser → usePage().props
│   │   └── ThemeToggle.tsx           # Без змін
│   └── Logo.tsx
├── Layouts/
│   ├── AuthenticatedLayout.tsx      # Адаптувати під Inertia
│   └── GuestLayout.tsx
└── Pages/                           # Фаза 4
```

#### 3.2 Зміни в UI компонентах

**Без змін** (чисті React + Tailwind):
- Усі `ui/*` компоненти (Button, Card, Input, Select, тощо)
- ThemeToggle, Logo, Spinner, Badge, Alert
- Charts компоненти (recharts)

**Потребують адаптації**:

| Компонент | Зміна |
|-----------|-------|
| `AuthenticatedLayout` | `useUser()` → `usePage<PageProps>().props.auth.user` |
| `AuthenticatedSidebar` | react-router `<Link>` → Inertia `<Link>`, `useLocation()` → `usePage().url` |
| `AuthenticatedTopbar` | `useUser()` → `usePage().props`, `useNavigate()` → `router.visit()` |
| `ProtectedRoute` | **Видалити** — middleware `auth` на Laravel стороні |
| `PublicRoute` | **Видалити** — middleware `guest` на Laravel стороні |

#### 3.3 Types міграція

**Скопіювати** (з адаптацією):
- `shared/types/entities.ts` → `resources/js/types/entities.ts`
- `shared/types/auth/` → `resources/js/types/auth.ts`
- `shared/types/api/` → `resources/js/types/api.ts` (спростити — Inertia props замість API responses)

**Додати** Inertia page props type:
```typescript
export interface PageProps {
    auth: {
        user: User | null;
    };
    flash: {
        success?: string;
        error?: string;
    };
}
```

---

### Фаза 4: Page Migration (5-7 днів)

Кожна сторінка мігрує за патерном:
1. Створити Web Controller → завантажити дані через існуючі Actions → `Inertia::render()`
2. Скопіювати React page component → `resources/js/Pages/`
3. Замінити API hooks на Inertia page props
4. Замінити API mutations на Inertia form submissions

#### 4.1 Addresses (CRUD)

**Routes**:
```php
Route::resource('addresses', AddressController::class)
    ->except(['show']);
```

**Controller**: `Modules/Address/Http/Controllers/Web/AddressController.php`

| Method | Action | Inertia Page |
|--------|--------|-------------|
| `index()` | `GetUserAddresses::run($user)` | `Addresses/Index` |
| `create()` | `GetAllRegions::run()`, `GetAllAddressTypes::run()` | `Addresses/Create` |
| `store(StoreAddressRequest)` | `CreateAddress::run(...)` | redirect → `addresses.index` |
| `edit($id)` | `GetUserAddress::run(...)` + reference data | `Addresses/Edit` |
| `update(UpdateAddressRequest, $id)` | `UpdateAddress::run(...)` | redirect → `addresses.index` |
| `destroy($id)` | `DeleteAddress::run(...)` | redirect → `addresses.index` |

**React файли**:

| Джерело | Ціль |
|---------|------|
| `modules/addresses/pages/AddressesPage.tsx` | `Pages/Addresses/Index.tsx` |
| `modules/addresses/pages/AddAddressPage.tsx` | `Pages/Addresses/Create.tsx` |
| `modules/addresses/pages/EditAddressPage.tsx` | `Pages/Addresses/Edit.tsx` |
| `modules/addresses/components/*` | `Pages/Addresses/Components/*` |

**Зміни в компонентах**:
- `useAddresses()` hook → page prop `addresses`
- `useCreateAddress()` → Inertia `router.post(route('addresses.store'), data)`
- `useRegions()` / `useAddressTypes()` → page props `regions`, `addressTypes`
- `useNavigate()` → `router.visit()`
- `react-router Link` → Inertia `Link`

#### 4.2 Meters (CRUD)

**Routes**:
```php
Route::resource('meters', MeterController::class)
    ->except(['show']);
Route::post('meters/{meter}/photo', [MeterController::class, 'uploadPhoto'])
    ->name('meters.photo');
```

**Controller**: `Modules/Meter/Http/Controllers/Web/MeterController.php`

| Method | Action | Inertia Page |
|--------|--------|-------------|
| `index(Request)` | `GetMetersByAddress::run($addressId)` | `Meters/Index` |
| `create()` | addresses + utility types + providers | `Meters/Create` |
| `store(StoreMeterRequest)` | `CreateMeter::run(...)` | redirect |
| `edit($id)` | `GetMeter::run($id)` + reference data | `Meters/Edit` |
| `update(UpdateMeterRequest, $id)` | `UpdateMeter::run(...)` | redirect |
| `destroy($id)` | `DeleteMeter::run(...)` | redirect |

**Адресна фільтрація**: Query parameter `?address_id=X` замість контексту.

#### 4.3 Readings (Batch Create + List)

**Routes**:
```php
Route::get('readings', [ReadingController::class, 'index'])->name('readings.index');
Route::get('readings/create', [ReadingController::class, 'create'])->name('readings.create');
Route::post('readings', [ReadingController::class, 'store'])->name('readings.store');
Route::delete('readings/{reading}', [ReadingController::class, 'destroy'])->name('readings.destroy');
```

**File upload**: Inertia підтримує file uploads через FormData. `PhotoDropzone` компонент залишається, дані відправляються через `router.post()` з `forceFormData: true`.

#### 4.4 Providers (CRUD)

**Routes**:
```php
Route::resource('providers', ServiceProviderController::class)
    ->except(['show']);
```

Аналогічно Addresses — CRUD через Actions.

#### 4.5 Settings (Multi-tab)

**Routes**:
```php
Route::get('settings', [SettingsController::class, 'index'])->name('settings');
Route::put('settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile');
Route::put('settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password');
Route::delete('settings/account', [SettingsController::class, 'destroyAccount'])->name('settings.account');
Route::post('settings/oauth/link', [SettingsController::class, 'linkOAuth'])->name('settings.oauth.link');
Route::delete('settings/oauth/{provider}', [SettingsController::class, 'unlinkOAuth'])->name('settings.oauth.unlink');
```

**Tabs**: Profile, Security, Account, Appearance, Connected Accounts. Tab selection через query param `?tab=profile`.

**Avatar upload**: `router.post()` з `forceFormData: true`, MediaLibrary обробляє як і раніше.

#### 4.6 Dashboard (Нові Actions)

**Routes**:
```php
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
```

**Нові Actions для створення**:

| Action | Input | Output |
|--------|-------|--------|
| `GetDashboardStats` | User | `{ addressCount, meterCount, lastReadingDate, totalConsumption }` |
| `GetConsumptionHistory` | User, period | `[ { date, type, value } ]` для line chart |
| `GetExpenseDistribution` | User, period | `[ { type, amount, percentage } ]` для pie chart |
| `GetRecentReadings` | User, limit | `[ { meter, value, date, consumption } ]` |

**Компоненти dashboard** (recharts):
- `ConsumptionChart.tsx` — line chart, приймає дані як props
- `ExpenseDistribution.tsx` — pie/bar chart
- `RecentReadingsTable.tsx` — таблиця
- `WelcomeHeader.tsx` — привітання + address selector
- `QuickActions.tsx` — кнопки швидких дій

---

### Фаза 5: Cleanup (1-2 дні)

#### 5.1 Видалити API layer

| Що | Де |
|----|-----|
| API routes | `Modules/*/routes/api.php` (усі 6 файлів) |
| API controllers | `Modules/*/Http/Controllers/*Controller.php` |
| RouteServiceProvider API binding | якщо є |
| CORS config | `config/cors.php` |
| Scramble config | `config/scramble.php`, `docs/api.json` |
| JWT config | `config/jwt.php` |
| API exception handlers | `bootstrap/app.php` (JWT-specific) |

#### 5.2 Видалити React-only код (не переноситься)

| Що | Причина |
|----|---------|
| `shared/api/` (apiClient, authService, всі services) | Замінено Inertia data flow |
| `shared/contexts/auth/` (AuthProvider, reducer, actions) | Замінено Inertia shared data |
| `shared/contexts/address/` (AddressProvider, AddressSyncProvider) | Замінено Inertia shared data |
| `shared/hooks/useAuth.ts`, `useUser.ts`, `useIsAuthenticated.ts` | Замінено `usePage().props` |
| `modules/*/api/` (addressService, meterService, тощо) | Замінено Inertia props |
| `modules/*/hooks/` що wrap API calls | Замінено Inertia props |
| `shared/constants/endpoints.ts` | API endpoints не потрібні |
| `shared/utils/caseTransform.ts` | camelCase↔snake_case не потрібно |
| `App.tsx` (router setup) | Замінено Inertia routing |

#### 5.3 Оновити Docker конфіг

`compose.yml` — додати pnpm/node до app контейнера або окремий dev script:
```yaml
# В app service додати Node.js для Vite dev server
# Або використовувати host-based Vite (pnpm run dev на хості)
```

`Dockerfile-dev` — додати Node.js 22 + pnpm для збірки:
```dockerfile
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && corepack enable \
    && corepack prepare pnpm@latest --activate
```

---

### Фаза 6: Testing (3-4 дні)

#### 6.1 Backend Feature Tests (нові)

```php
// tests/Feature/Web/Auth/LoginTest.php
it('renders login page', function () {
    $this->get('/login')
        ->assertInertia(fn (Assert $page) =>
            $page->component('Auth/Login')
        );
});

it('authenticates user', function () {
    $user = User::factory()->create(['password' => 'password']);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect('/');

    $this->assertAuthenticatedAs($user);
});
```

#### 6.2 Тести для кожного контролера

| Контролер | Тест кейси |
|-----------|-----------|
| LoginController | Render page, successful login, invalid credentials, throttling |
| RegisterController | Render page, successful registration, validation errors, duplicate email |
| OAuthController | Redirect to provider, callback creates user, callback logs in existing |
| LogoutController | Logout + session invalidate |
| AddressController | Index, Create, Store (valid/invalid), Edit, Update, Destroy |
| MeterController | Index (filtered by address), Create, Store, Edit, Update, Destroy |
| ReadingController | Index, Create, Store (batch), Destroy |
| ServiceProviderController | CRUD |
| SettingsController | Profile update, password change, avatar upload, OAuth link/unlink |
| DashboardController | Index with real data |

#### 6.3 Існуючі тести

Action unit tests повинні пройти без змін (Actions незалежні від auth transport). Можливі зміни:
- Тести що мокають JWT guard → змінити на web guard
- Тести що тестують API endpoints → замінити на web controller тести

---

## 5. Маппінг Routes: React Router → Inertia

| React Route | Inertia Route | Controller | Inertia Page |
|-------------|--------------|------------|-------------|
| `/` | `GET /` | `DashboardController@index` | `Dashboard/Index` |
| `/login` | `GET /login` | `LoginController@show` | `Auth/Login` |
| `/register` | `GET /register` | `RegisterController@show` | `Auth/Register` |
| `/auth/callback` | `GET /auth/{provider}/callback` | `OAuthController@callback` | — (redirect) |
| `/logout` | `POST /logout` | `LogoutController@destroy` | — (redirect) |
| `/addresses` | `GET /addresses` | `AddressController@index` | `Addresses/Index` |
| `/addresses/new` | `GET /addresses/create` | `AddressController@create` | `Addresses/Create` |
| `/addresses/:id/edit` | `GET /addresses/{id}/edit` | `AddressController@edit` | `Addresses/Edit` |
| `/meters` | `GET /meters` | `MeterController@index` | `Meters/Index` |
| `/meters/new` | `GET /meters/create` | `MeterController@create` | `Meters/Create` |
| `/meters/:id/edit` | `GET /meters/{id}/edit` | `MeterController@edit` | `Meters/Edit` |
| `/readings/new` | `GET /readings/create` | `ReadingController@create` | `Readings/Create` |
| `/providers` | `GET /providers` | `ServiceProviderController@index` | `Providers/Index` |
| `/providers/new` | `GET /providers/create` | `ServiceProviderController@create` | `Providers/Create` |
| `/settings` | `GET /settings` | `SettingsController@index` | `Settings/Index` |
| `/profile` | Redirect to `/settings?tab=profile` | — | — |

---

## 6. Data Flow: До і Після

### До (API + React SPA)
```
React Component
  → useAddresses() hook
    → addressService.getAll()
      → axios.get('/api/v1/address')
        → Laravel API Controller
          → GetUserAddresses Action
            → Response JSON
              → axios interceptor (snake→camel)
                → React state update
                  → Re-render
```

### Після (Inertia)
```
Browser request GET /addresses
  → Laravel AddressController@index
    → GetUserAddresses Action
      → Inertia::render('Addresses/Index', ['addresses' => AddressResource::collection($addresses)])
        → React component receives addresses as props
          → Render
```

### Form submission: До і Після

**До**:
```
React Form → onSubmit → addressService.create(data) → axios.post('/api/v1/address')
  → API Controller → CreateAddress Action → JSON response
    → React updates state → navigate to /addresses
```

**Після**:
```
React Form → Inertia useForm → router.post('/addresses', data)
  → Laravel AddressController@store → CreateAddress Action → redirect('/addresses')
    → Inertia re-renders Addresses/Index with fresh data
```

---

## 7. Що не переноситься з React проєкту

| Файл/Директорія | Причина |
|-----------------|---------|
| `src/shared/api/` | Axios API layer замінено Inertia |
| `src/shared/contexts/auth/` | Auth state з Inertia shared data |
| `src/shared/contexts/address/` | Address data з Inertia props |
| `src/shared/hooks/useAuth*` | `usePage().props.auth` |
| `src/shared/utils/caseTransform.ts` | Не потрібно з Inertia |
| `src/shared/constants/endpoints.ts` | API endpoints видалені |
| `src/App.tsx` | React Router setup замінено Inertia |
| `src/main.tsx` | Замінено `app.tsx` entry point |
| `src/modules/auth/pages/OAuthCallbackPage.tsx` | Server-side callback |
| `src/modules/auth/pages/LogoutPage.tsx` | Server-side logout |
| `e2e/` tests | Потрібно переписати під нову архітектуру |
| `vitest.config.ts` | Нова конфігурація потрібна |
| `pnpm-lock.yaml` | Новий lockfile генерується |
| `.env`, `.env.example` | Нові env vars (без VITE_API_*) |

---

## 8. Нові Environment Variables

**Видалити** (більше не потрібні):
```
JWT_SECRET
JWT_TTL
JWT_REFRESH_TTL
JWT_ALGO
JWT_BLACKLIST_ENABLED
CORS_ALLOWED_ORIGINS
CORS_SUPPORTS_CREDENTIALS
```

**Залишити**:
```
SESSION_DRIVER=redis
SESSION_LIFETIME=120
```

**Frontend** (Vite):
```
VITE_APP_NAME=Komunalka
```

---

## 9. Ризики та мітигація

| Ризик | Серйозність | Мітигація |
|-------|------------|-----------|
| OAuth flow поломка | Висока | Тестувати з реальними Google/GitHub OAuth apps на staging |
| Втрата даних при міграції refresh_tokens | Низька | Таблицю можна залишити, просто перестати використовувати |
| File upload через Inertia | Середня | Тестувати PhotoDropzone з `forceFormData: true` |
| Tailwind v4 конфлікти | Низька | CSS копіюється as-is, Tailwind v4 не потребує config файлу |
| Vite 8 breaking changes | Середня | Перевірити сумісність laravel-vite-plugin з Vite 8 |
| Dashboard mock → real data | Середня | Створити Actions з тестами до міграції UI |
| ~31K рядків коду для міграції | Висока | Мігрувати посторінково, тестувати кожну сторінку окремо |

---

## 10. Оцінка часу

| Фаза | Тривалість | Залежності |
|------|-----------|-----------|
| 1. Foundation Setup | 2-3 дні | — |
| 2. Auth Migration | 2-3 дні | Фаза 1 |
| 3. UI Components | 1-2 дні | Фаза 1 |
| 4. Page Migration | 5-7 днів | Фази 2, 3 |
| 5. Cleanup | 1-2 дні | Фаза 4 |
| 6. Testing | 3-4 дні | Фаза 5 |
| **Всього** | **14-21 день** | |

Фази 2 і 3 можуть виконуватись паралельно.

---

## 11. Чек-лист готовності до production

- [ ] `pnpm run build` — збірка без помилок
- [ ] `php artisan test` — всі тести проходять
- [ ] `composer run phpstan` — рівень 7 без помилок
- [ ] `composer run pint` — код відформатовано
- [ ] Login/Register через email працює
- [ ] OAuth Google працює
- [ ] OAuth GitHub працює
- [ ] Addresses CRUD працює
- [ ] Meters CRUD працює
- [ ] Readings batch create + photo upload працює
- [ ] Service Providers CRUD працює
- [ ] Settings: profile update + avatar upload працює
- [ ] Settings: password change працює
- [ ] Settings: OAuth link/unlink працює
- [ ] Dashboard показує реальні дані
- [ ] Dark mode працює
- [ ] Responsive design (mobile) працює
- [ ] Flash messages відображаються
- [ ] Validation errors відображаються на формах
- [ ] 404 сторінка працює
- [ ] Unauthorized redirect на login працює
