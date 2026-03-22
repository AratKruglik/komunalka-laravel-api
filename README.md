# Komunalka

Full-stack веб-додаток для управління комунальними послугами: лічильники, показники споживання, постачальники, тарифи та адреси. Побудований на Laravel 13 + Inertia.js v2 + React 19.

## Технологічний стек

- **PHP 8.2+** / Laravel 13 / FrankenPHP (Octane)
- **React 19** / TypeScript / Inertia.js v2
- **Tailwind CSS v4** / Tailwind Variants
- **PostgreSQL 17** / Redis 7.2
- **Сесійна автентифікація** з OAuth (Google, GitHub)
- **Pest 4** / PHPStan level 7 / Laravel Pint

## Вимоги

- Docker та Docker Compose

## Локальне налаштування

1. Клонувати репозиторій:

```bash
git clone <repo-url> && cd komunalka-laravel-api
```

2. Скопіювати файл середовища та налаштувати:

```bash
cp .env.example .env
```

Відредагувати `.env` — встановити облікові дані бази даних, Redis, ключі OAuth. Значення за замовчуванням сумісні з Docker Compose сервісами.

3. Запустити всі сервіси:

```bash
docker compose up -d
```

Запускаються: **app** (FrankenPHP), **db** (PostgreSQL), **db-test** (тестова БД), **redis**, **schedule** (cron), **ci** (CI pipeline).

4. Виконати початкове налаштування в контейнері:

```bash
docker compose exec app composer setup
```

Команда встановлює залежності, генерує ключ, запускає міграції, встановлює npm-пакети та збирає frontend.

Додаток доступний за адресою **https://localhost**.

## Сервіси

| Сервіс | URL | Призначення |
|--------|-----|-------------|
| App | https://localhost | Основний веб-додаток |
| WebSockets | ws://localhost:8080 | Real-time події (Reverb) |
| PostgreSQL | localhost:5432 | База даних |
| Redis | localhost:6379 | Кеш та черги |

## Локальний HTTPS

HTTPS працює одразу через директиву Caddy `tls internal`, яка генерує самопідписаний сертифікат. HTTP-запити до `http://localhost` автоматично перенаправляються на HTTPS.

Щоб довіряти локальному CA на macOS (прибирає попередження браузера):

```bash
docker compose cp app:/data/caddy/pki/authorities/local/root.crt ./caddy-root.crt
sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain ./caddy-root.crt
rm ./caddy-root.crt
```

Перевірити що HTTPS працює:

```bash
curl -k https://localhost/health
```

## Сторінки додатку

| Сторінка | Маршрути | Опис |
|----------|----------|------|
| **Dashboard** | `/` | Статистика, графіки споживання, останні показники |
| **Адреси** | `/addresses` | Управління адресами (CRUD) |
| **Лічильники** | `/meters` | Управління лічильниками та фото |
| **Показники** | `/readings` | Внесення та перегляд показників |
| **Постачальники** | `/providers` | Постачальники послуг та тарифи |
| **Налаштування** | `/settings` | Профіль, безпека, OAuth прив'язки |
| **Auth** | `/login`, `/register` | Вхід та реєстрація |

## Запуск тестів

```bash
docker compose exec app php artisan test --compact --parallel
```

Запуск за типом:

```bash
docker compose exec app php artisan test tests/Browser/
docker compose exec app php artisan test tests/Feature/
```

Запуск із покриттям:

```bash
docker compose exec app php artisan test --coverage
```

## Якість коду

```bash
# Форматування коду
docker compose exec app vendor/bin/pint --dirty

# Статичний аналіз
docker compose exec app vendor/bin/phpstan analyse --memory-limit=512M

# Модернізація коду
docker compose exec app vendor/bin/rector process --dry-run
```

## Структура проєкту

```
app/                        Базові класи (Repository contracts)
Modules/
  Address/                  Адреси, типи адрес, регіони
  Auth/                     Автентифікація, OAuth, налаштування
  Billing/                  Постачальники послуг, тарифи
  Export/                   Експорт CSV/PDF
  Meter/                    Лічильники, показники, фото
  Shared/                   Типи послуг, валюти, довідники
resources/
  js/
    components/             Shared React компоненти
    layouts/                AuthenticatedLayout, GuestLayout
    pages/                  React TSX сторінки (Inertia)
    types/                  TypeScript інтерфейси
tests/
  Browser/                  Pest 4 browser tests (рендеринг Inertia сторінок)
  Feature/
    Web/                    HTTP feature tests
```

Кожен модуль містить: `Actions/`, `DTOs/`, `Http/Controllers/Web/`, `Http/Requests/`, `Models/`, `Repositories/`, `routes/web.php`, `database/`, `tests/`.
