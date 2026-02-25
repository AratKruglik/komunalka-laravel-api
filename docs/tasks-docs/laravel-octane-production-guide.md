# Laravel Octane: продакшн-гайд для Docker та Kubernetes

Вичерпний гайд з усіма відомими проблемами, крайніми випадками та конфігураціями для розгортання Laravel Octane у продакшні на Swoole, FrankenPHP та RoadRunner.

---

## Зміст

1. [Порівняння драйверів](#1-порівняння-драйверів)
2. [Моделі конкурентності](#2-моделі-конкурентності)
3. [Бенчмарки продуктивності](#3-бенчмарки-продуктивності)
4. [Витоки пам'яті та забруднення стану](#4-витоки-памяті-та-забруднення-стану)
5. [Система listeners Octane](#5-система-listeners-octane)
6. [Налаштування воркерів та рециклінг](#6-налаштування-воркерів-та-рециклінг)
7. [Управління з'єднаннями з БД](#7-управління-зєднаннями-з-бд)
8. [Завантаження файлів та тимчасові файли](#8-завантаження-файлів-та-тимчасові-файли)
9. [Відомі проблемні пакети](#9-відомі-проблемні-пакети)
10. [Повний довідник octane.php](#10-повний-довідник-octanephp)
11. [Оптимізація Docker-образів](#11-оптимізація-docker-образів)
12. [Health checks та K8s проби](#12-health-checks-та-k8s-проби)
13. [Коректне завершення та SIGTERM](#13-коректне-завершення-та-sigterm)
14. [Розгортання без простою](#14-розгортання-без-простою)
15. [Горизонтальне масштабування](#15-горизонтальне-масштабування)
16. [Захист від OOM Killer](#16-захист-від-oom-killer)
17. [Логування в контейнерах](#17-логування-в-контейнерах)
18. [Ефемерна файлова система та сховище](#18-ефемерна-файлова-система-та-сховище)
19. [Змінні оточення](#19-змінні-оточення)
20. [SSL/TLS термінація](#20-ssltls-термінація)
21. [Конфігурація зворотного проксі](#21-конфігурація-зворотного-проксі)
22. [Повні маніфести Kubernetes](#22-повні-маніфести-kubernetes)
23. [Docker Compose для продакшну](#23-docker-compose-для-продакшну)
24. [HPA автомасштабування](#24-hpa-автомасштабування)
25. [Питання безпеки](#25-питання-безпеки)
26. [Обмеження частоти запитів](#26-обмеження-частоти-запитів)
27. [Обробка таймаутів](#27-обробка-таймаутів)
28. [Обробка помилок та падіння воркерів](#28-обробка-помилок-та-падіння-воркерів)
29. [Відмінності поведінки middleware](#29-відмінності-поведінки-middleware)
30. [Кешування DNS-резолвінгу](#30-кешування-dns-резолвінгу)
31. [Моніторинг та спостережуваність](#31-моніторинг-та-спостережуваність)
32. [Чеклист міграції: PHP-FPM -> Octane](#32-чеклист-міграції-php-fpm---octane)
33. [Конфігурація для кожного драйвера](#33-конфігурація-для-кожного-драйвера)
34. [Джерела](#34-джерела)

---

## 1. Порівняння драйверів

| Характеристика | FrankenPHP | Swoole | RoadRunner |
|----------------|-----------|--------|------------|
| Модель воркерів | Потоки (Go goroutines) | Форковані процеси | Окремі PHP-процеси |
| Ізоляція пам'яті | Слабка (спільний процес) | Сильна (окремий процес) | Найсильніша (окремий процес + IPC) |
| Конкурентний I/O | Ні | Так (корутини) | Ні |
| Потрібне розширення | Ні (вбудований бінарник) | Так (PECL `swoole`) | Ні (Go-бінарник) |
| Вплив падіння воркера | Може зачепити інших | Ізольовано | Ізольовано |
| Пулінг з'єднань | Тільки зовнішній | Вбудований (Swoole pool) | Тільки зовнішній |
| Вбудований TLS | Так (Caddy) | Ні | Частково (плагін) |
| HTTP/2, HTTP/3 | Так | Тільки HTTP/2 | Ні |
| Early Hints (103) | Так | Ні | Ні |
| WebSocket | Через Caddy/Mercure | Вбудований | Через плагіни |
| `Octane::concurrently()` | Ні | Так | Ні |
| Tick/інтервальні задачі | Ні | Так | Ні |
| Octane кеш (in-memory) | Ні | Так (Swoole tables) | Ні |
| Сумісність з Xdebug | Так | Ні | Так |
| Сумісність з APM | Добра | Обмежена | Добра |
| Обробка SIGTERM | Баг (відома проблема) | Добра | Найкраща |
| Віддача статичних файлів | Вбудована (Caddy) | Потрібен проксі | Потрібен проксі |
| Зрілість | Молодий, але підтримка PHP Foundation | Найбільш обкатаний | Обкатаний, ентерпрайз |
| Розмір образу (Alpine) | ~150-250 МБ | ~200-300 МБ | ~180-280 МБ |

---

## 2. Моделі конкурентності

### FrankenPHP
Побудований на Caddy (Go). Використовує планувальник горутин Go. Кожен PHP-воркер працює в постійному потоці. FrankenPHP розподіляє запити між доступними воркерами з Go HTTP-сервера. За замовчуванням: **2x кількість ядер CPU** воркерів.

```
Go HTTP-сервер -> горутина на запит -> відправка в пул PHP-воркерів
```

### Swoole
PHP-розширення (C). Головний процес форкає воркер-процеси. Кожен воркер — окремий процес ОС. Корутини всередині одного воркера дозволяють неблокуючий I/O — один воркер обробляє багато конкурентних з'єднань, поступаючись під час запитів до БД, HTTP-викликів тощо.

```
Воркер 1: Запит A (запит до БД -> yield) -> Запит B (обробка) -> Запит A (продовження)
Воркер 2: Запит C -> Запит D -> ...
```

Hook-прапорці перетворюють блокуючі функції PHP на неблокуючі:
```php
'swoole' => [
    'options' => [
        'hook_flags' => SWOOLE_HOOK_ALL,
    ],
],
```

### RoadRunner
Go HTTP-сервер, що керує пулом PHP-воркер-процесів через пайпи (stdin/stdout, Protocol Buffers). Один запит на воркер за раз (послідовно). Go-шар забезпечує конкурентність. Немає спільної пам'яті між воркерами.

```
Go HTTP-сервер -> Пул воркерів (N процесів) -> кожен обробляє 1 запит за раз
```

---

## 3. Бенчмарки продуктивності

### Сирі цифри (Laravel Pest Stressless, MacBook M1 Pro)

| Метрика | FrankenPHP | RoadRunner | Swoole |
|---------|-----------|------------|--------|
| Медіанна затримка (конкурентність 1) | 0.88 мс | 2.61 мс | 4.94 мс |
| Медіанна затримка (конкурентність 8) | 1.59 мс | 4.00 мс | 5.39 мс |
| Пропускна здатність vs PHP-FPM | ~5x | ~3x | ~2-4x |

**Застереження:**
- FrankenPHP та RoadRunner виграють на CPU-навантажених або важких для бутстрапу фреймворку завданнях
- Swoole досягає найвищої пропускної здатності для I/O-навантажених завдань (багато запитів до БД, зовнішніх API), бо корутини дозволяють одному воркеру мультиплексувати I/O
- Усі три драйвери дають покращення в 2-4x порівняно з PHP-FPM
- Завжди бенчмаркте з реальним застосунком

---

## 4. Витоки пам'яті та забруднення стану

### Фундаментальна проблема

У PHP-FPM кожен запит бутстрапить свіжий екземпляр застосунку. В Octane застосунок завантажується **один раз** і залишається в пам'яті між усіма запитами в межах воркера. Будь-який синглтон, що зберігає дані запиту, накопичуватиме стан нескінченно.

### Паттерн 1: Синглтони, що накопичують стан

```php
// ВИТІКАЄ: Синглтон-сервіс, що накопичує дані
class ReportService
{
    private array $processedItems = [];

    public function process(Item $item): void
    {
        $this->processedItems[] = $item; // росте нескінченно між запитами
    }
}
```

**Виправлення:**
```php
// ВИПРАВЛЕННЯ 1: Додати до масиву 'flush' в config/octane.php
'flush' => [
    ReportService::class,
],

// ВИПРАВЛЕННЯ 2: Не реєструвати як синглтон, якщо тримає стан запиту
$this->app->bind(ReportService::class); // transient, не singleton

// ВИПРАВЛЕННЯ 3: Використовувати scoped-прив'язку (скидається автоматично щозапиту)
$this->app->scoped(ReportService::class);
```

### Паттерн 2: Статичні властивості

```php
// НЕБЕЗПЕЧНО: Статична властивість живе між запитами
class OrganizationMiddleware
{
    public function handle($request, Closure $next)
    {
        OrganizationService::$currentOrg = $request->header('X-Org-Id');
        return $next($request);
    }
}
// Якщо наступний запит не має заголовка,
// OrganizationService::$currentOrg все ще тримає попереднє значення
```

**Виправлення:**
- Уникайте статичних властивостей — використовуйте контейнер сервісів
- Використовуйте scoped-прив'язки: `$this->app->scoped(OrganizationService::class)`
- Додайте listener на `RequestReceived` для очищення статичного стану

### Паттерн 3: Event-слухачі, що накопичують посилання

```php
// ВИТІКАЄ: Замикання захоплює зростаючий масив
class SomeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(OrderCreated::class, function ($event) {
            $this->processedOrders[] = $event->order; // росте нескінченно
        });
    }
}
```

### Паттерн 4: Обробка великих колекцій

```php
// ВИТІКАЄ: Завантажує ВСІ записи в пам'ять — залишається в пам'яті воркера
$records = MeterReading::all();

// ВИПРАВЛЕННЯ: Використовувати чанки та генератори
MeterReading::query()->chunk(1000, function ($readings) {
    // Обробляє чанк, потім може бути зібраний GC
});
```

### Паттерн 5: Кешування об'єктів у властивостях сервісу

```php
// ВИТІКАЄ: Сервіс кешує між запитами
class TariffCalculator
{
    private ?Collection $cachedTariffs = null;

    public function calculate(): Money
    {
        $this->cachedTariffs ??= Tariff::all(); // ніколи не звільняється, застарілі дані
    }
}

// ВИПРАВЛЕННЯ: Використовувати Redis-кеш з TTL
class TariffCalculator
{
    public function calculate(): Money
    {
        $tariffs = Cache::remember('tariffs', 300, fn () => Tariff::all());
    }
}
```

### Паттерн 6: Витік пам'яті фасаду Http

Відома проблема (GitHub #481): фасад `Http` накопичує дані відповідей у пам'яті між запитами.

**Виправлення:** Додайте HTTP-клієнт до масиву `flush` або створюйте нові екземпляри Guzzle на кожен запит.

### Критично: витік при `APP_DEBUG=true`

При `APP_DEBUG=true` лог запитів та дані debug bar накопичуються між запитами. **Ніколи не запускайте `APP_DEBUG=true` у продакшні з Octane.**

---

## 5. Система listeners Octane

### `prepareApplicationForNextRequest()` скидає:

| Listener | Що скидає |
|----------|----------|
| `FlushLocaleState` | Локаль застосунку, локаль Carbon |
| `FlushQueuedCookies` | Черга cookie jar |
| `FlushSessionState` | Дані сесії попереднього запиту |
| `FlushAuthenticationState` | Auth guards (забуває всі екземпляри guard'ів) |
| `EnforceRequestScheme` | Схема URL-генератора |
| `EnsureRequestServerPortMatchesScheme` | Відповідність порту сервера |
| `GiveNewRequestInstanceToApplication` | Прив'язує новий Request до контейнера |
| `GiveNewRequestInstanceToPaginator` | Поточна сторінка/шлях пагінатора |

### `prepareApplicationForNextOperation()` скидає:

| Listener | Що скидає |
|----------|----------|
| `CreateConfigurationSandbox` | Репозиторій конфігурації (клонується на кожен запит) |
| `CreateUrlGeneratorSandbox` | URL-генератор |
| `GiveNewApplicationInstanceTo*` | Менеджери: Database, Filesystem, Mail, Cache, Session, Queue, Broadcast, Notification, Log, Validation, View, Router, HttpKernel, AuthorizationGate, PipelineHub |
| `FlushDatabaseRecordModificationState` | Відстеження модифікацій записів БД |
| `FlushDatabaseQueryLog` | Лог запитів (запобігає зростанню пам'яті) |
| `FlushArrayCache` | In-memory array-кеш |
| `FlushLogContext` | Спільний контекст логу |
| `FlushMonologState` | Стан процесорів Monolog |
| `FlushStrCache` | Кеші хелперів `Str::` |
| `FlushTranslatorCache` | Кеш перекладів |
| `FlushVite` | Маніфест Vite |
| `PrepareInertiaForNextOperation` | Спільні дані Inertia |
| `PrepareSocialiteForNextOperation` | Стан Socialite |

### `OperationTerminated` обробляє:

| Listener | Що робить |
|----------|----------|
| `FlushOnce` | Очищує кеш мемоізації `spatie/once` |
| `FlushTemporaryContainerInstances` | Викликає `forgetScopedInstances()` + очищує прив'язки з `octane.flush` |

### Закоментовані listeners (потрібно увімкнути вручну)

```php
// config/octane.php — закоментовані за замовчуванням, але важливі:

RequestTerminated::class => [
    FlushUploadedFiles::class,     // УВІМКНУТИ при роботі із завантаженням файлів
],

OperationTerminated::class => [
    FlushOnce::class,
    FlushTemporaryContainerInstances::class,
    CollectGarbage::class,          // УВІМКНУТИ для агресивного управління пам'яттю
    // DisconnectFromDatabases::class, // УВІМКНУТИ якщо НЕ використовуєте PgBouncer
],
```

---

## 6. Налаштування воркерів та рециклінг

### Формула кількості воркерів

| Тип навантаження | Рекомендовані воркери | Обґрунтування |
|-----------------|----------------------|---------------|
| CPU-навантажений | `кількість_ядер + 1` | Додатковий воркер компенсує переключення контексту |
| I/O-навантажений (API, БД) | `ядра * 2` до `ядра * 3` | Воркери блокуються на I/O |
| Swoole з корутинами | `кількість_ядер` | Корутини самі мультиплексують I/O |

### Конфігурація для кожного драйвера

**FrankenPHP:**
```bash
php artisan octane:frankenphp --host=0.0.0.0 --port=80 --workers=4 --max-requests=500
```
Воркери за замовчуванням: 2x ядер CPU.

**Swoole:**
```php
// config/octane.php
'swoole' => [
    'options' => [
        'worker_num' => max(4, swoole_cpu_num() * 2),
        'task_worker_num' => swoole_cpu_num() * 2,
        'max_request' => 500,
        'max_wait_time' => 60,
        'enable_reuse_port' => true,
        'reload_async' => true,
    ],
],
```

**RoadRunner (.rr.yaml):**
```yaml
http:
  pool:
    num_workers: 4            # 0 = автовизначення кількості ядер
    max_jobs: 500             # перезапуск воркера після N запитів
    allocate_timeout: 60s
    destroy_timeout: 60s
    supervisor:
      max_worker_memory: 512  # МБ — перезапуск при перевищенні
      ttl: 3600               # перезапуск кожну годину незалежно
      idle_ttl: 60s           # вбити неактивних воркерів
```

### Стратегія рециклінгу

| Налаштування | Консервативне | Збалансоване | Агресивне |
|-------------|---------------|-------------|-----------|
| max_requests | 250 | 500 | 2000 |
| Поріг garbage (МБ) | 64 | 128 | 256 |
| Воркери | кількість ядер | ядра * 2 | ядра * 4 |
| TTL супервізора | 1800с | 3600с | -- |

### Споживання пам'яті на воркер

Кожен воркер Octane споживає приблизно 30-80 МБ. З 4 воркерами:
- Базові витрати: ~50 МБ
- На воркер: ~60 МБ x 4 = 240 МБ
- Запас для піків: ~220 МБ
- Разом: ~512 МБ

---

## 7. Управління з'єднаннями з БД

### Основна проблема

Octane перевикористовує одне з'єднання з базою на воркер між усіма запитами.

### Застарілі з'єднання

Коли сервер БД закриває неактивні з'єднання, постійне з'єднання воркера стає застарілим. Наступний запит отримає помилку з'єднання.

**Виправлення для PostgreSQL:**
```php
// config/database.php
'pgsql' => [
    'options' => [
        PDO::ATTR_PERSISTENT => false,
    ],
],
```

### Витік стану транзакцій

Якщо Запит A починає транзакцію і падає (виняток перед commit/rollback), Запит B успадковує відкриту транзакцію.

**Виправлення:**
```php
class EnsureNoOpenTransactions
{
    public function handle($event): void
    {
        $db = $event->sandbox->make('db');
        foreach ($db->getConnections() as $connection) {
            while ($connection->transactionLevel() > 0) {
                $connection->rollBack();
            }
        }
    }
}
```

### Забагато з'єднань

N воркерів x M серверів = N*M постійних з'єднань. 10 серверів по 8 воркерів = 80 з'єднань — до queue-воркерів, планувальників тощо.

**Рекомендація: використовувати PgBouncer:**
```ini
# pgbouncer.ini
[databases]
komunalka = host=postgres port=5432 dbname=komunalka

[pgbouncer]
pool_mode = transaction
max_client_conn = 200
default_pool_size = 20
reserve_pool_size = 5
server_idle_timeout = 300
```

```php
// config/database.php
'pgsql' => [
    'host' => env('DB_HOST', 'pgbouncer'),
    'port' => env('DB_PORT', '6432'),
],
```

### Компроміс `DisconnectFromDatabases`

- **З PgBouncer:** увімкнути — найбезпечніший варіант, PgBouncer обробляє пулінг
- **Без PgBouncer:** залишити вимкненим, але додати listener `EnsureNoOpenTransactions`

---

## 8. Завантаження файлів та тимчасові файли

### `FlushUploadedFiles` закоментований за замовчуванням

```php
// config/octane.php — рядок 84
RequestTerminated::class => [
    // FlushUploadedFiles::class,  // <-- ЗАКОМЕНТОВАНО
],
```

**Без цього:** кожне завантаження файлу створює тимчасовий файл у `/tmp`, який ніколи не видаляється. Згодом `/tmp` заповнюється.

### Проблеми завантаження файлів у Swoole

- Swoole має хардкоджений `package_max_length`, що перевизначає PHP `upload_max_filesize`
- Помірно важкі завантаження можуть повністю зупинити Octane (GitHub #350)

**Виправлення для Swoole:**
```php
'swoole' => [
    'options' => [
        'package_max_length' => 100 * 1024 * 1024, // 100МБ
    ],
],
```

### Максимальний розмір запиту по драйверах

| Драйвер | Де налаштовується | За замовчуванням | Параметр |
|---------|-------------------|-----------------|----------|
| Swoole | `config/octane.php` | ~2МБ | `package_max_length` |
| RoadRunner | `.rr.yaml` | 1МБ | `http.maxRequestSize` |
| FrankenPHP | Caddyfile | Без обмежень | `request_body { max_size 100MB }` |

---

## 9. Відомі проблемні пакети

### spatie/laravel-permission

Дозволи кешуються у статичному кеші всередині воркера. При зміні ролей тільки воркер, що виконав зміну, бачить оновлення.

```php
// config/octane.php
'flush' => [
    \Spatie\Permission\PermissionRegistrar::class,
],
```

### spatie/laravel-medialibrary

Операції завантаження файлів ламаються після кількох завантажень у тому самому воркері. Тимчасові директорії накопичуються.

**Виправлення:** увімкнути `FlushUploadedFiles::class` та забезпечити очищення тимчасових директорій.

### spatie/once

Мемоізовані значення зберігаються між запитами, якщо `FlushOnce` не активний (активний за замовчуванням).

### laravel/horizon

Синглтон `Stopwatch` нескінченно накопичує записи `$timers`.

```php
// config/horizon.php
'defaults' => [
    'supervisor-1' => [
        'maxJobs' => 1000,
        'maxTime' => 3600,
        'memory' => 256,
    ],
],
```

### php-open-source-saver/jwt-auth

Відома проблема (#222): `Auth::user()` може повертати `null` на наступних запитах. Listener `FlushAuthenticationState` мав би це обробляти, але перевірте при тестуванні.

---

## 10. Повний довідник octane.php

### `server` (string)
```php
'server' => env('OCTANE_SERVER', 'frankenphp'),
```
Підтримуються: `roadrunner`, `swoole`, `frankenphp`.

### `https` (bool)
```php
'https' => env('OCTANE_HTTPS', false),
```
Встановити `true` при роботі за SSL-термінуючим проксі.

### `listeners` (array)
Прив'язує події життєвого циклу Octane до класів listeners. Див. [Розділ 5](#5-система-listeners-octane) для повного довідника.

### `warm` (array)
```php
'warm' => [
    ...Octane::defaultServicesToWarm(),
],
```
Прив'язки, що резолвляться та кешуються при бутстрапі воркера. Додайте часто використовувані синглтони для зменшення витрат на кожен запит.

### `flush` (array)
```php
'flush' => [],
```
Прив'язки, що примусово пере-резолвляться на кожен запит. Додайте сервіси, що зберігають стан користувача.

### `tables` (array) — тільки Swoole
```php
'tables' => [
    'example:1000' => [
        'name' => 'string:1000',
        'votes' => 'int',
    ],
],
```
Таблиці спільної пам'яті між усіма воркерами. Втрачаються при перезапуску.

### `cache` (array) — тільки Swoole
```php
'cache' => [
    'rows' => 1000,
    'bytes' => 10000,
],
```
Конфігурує `Cache::store('octane')`. 2М+ операцій/сек. Втрачається при перезапуску.

### `garbage` (int)
```php
'garbage' => 50,
```
Поріг у МБ для примусового GC. Продакшн: 50-128 МБ.

### `max_execution_time` (int)
```php
'max_execution_time' => 30,
```
Секунди до примусового завершення запиту. 0 — без обмежень.

### `watch` (array)
Тільки для розробки. Шляхи для спостереження при `--watch`.

---

## 11. Оптимізація Docker-образів

### FrankenPHP: багатоетапна збірка

```dockerfile
# Етап 1: Збірка PHP-розширень
FROM dunglas/frankenphp:1.9.1-php8.4-alpine AS builder

COPY --from=composer:2.8.5 /usr/bin/composer /usr/bin/

RUN set -ex && \
    apk add --no-cache \
        postgresql-dev libzip-dev libpng-dev libjpeg-turbo-dev \
        freetype-dev icu-dev imagemagick-dev libheif-dev \
        linux-headers build-base autoconf cmake make && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) \
        bcmath pdo_pgsql zip gd intl exif sockets pcntl && \
    pecl install redis imagick && \
    docker-php-ext-enable redis imagick

# Етап 2: Продакшн-образ (без інструментів збірки)
FROM dunglas/frankenphp:1.9.1-php8.4-alpine

RUN apk add --no-cache \
    libpq libzip libpng libjpeg-turbo freetype icu-libs \
    imagemagick libheif libavif \
    jpegoptim optipng pngquant gifsicle

COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

RUN echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/memory-limit.ini && \
    echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache-prod.ini && \
    echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/opcache-prod.ini && \
    echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/opcache-prod.ini && \
    echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache-prod.ini

WORKDIR /var/www

COPY --from=composer:2.8.5 /usr/bin/composer /usr/bin/
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

COPY . .
RUN php artisan config:cache && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan event:cache

EXPOSE 80 443 443/udp
ENTRYPOINT ["php", "artisan", "octane:frankenphp"]
CMD ["--host=0.0.0.0", "--port=443", "--https", "--http-redirect"]
```

### Swoole Dockerfile

```dockerfile
FROM php:8.4-cli-alpine AS builder
RUN apk add --no-cache $PHPIZE_DEPS linux-headers openssl-dev && \
    pecl install swoole && \
    docker-php-ext-enable swoole

FROM php:8.4-cli-alpine
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
# ... копіювання застосунку, composer install, команди кешування ...
ENTRYPOINT ["php", "artisan", "octane:start"]
CMD ["--server=swoole", "--host=0.0.0.0", "--port=8000", "--workers=auto"]
```

### RoadRunner Dockerfile

```dockerfile
FROM php:8.4-cli-alpine AS base
# Встановлення PHP-розширень...

FROM ghcr.io/roadrunner-server/roadrunner:2024 AS roadrunner

FROM base
COPY --from=roadrunner /usr/bin/rr /usr/local/bin/rr
# ... копіювання застосунку ...
ENTRYPOINT ["php", "artisan", "octane:start"]
CMD ["--server=roadrunner", "--host=0.0.0.0", "--port=8000"]
```

### Ключові практики оптимізації

- Завжди використовувати Alpine-варіанти (`-alpine`) — зменшує розмір на 40-60%
- Використовувати `.dockerignore`: виключати `.git/`, `node_modules/`, `tests/`, `docker/`, `.env`
- `composer install --no-dev --optimize-autoloader`
- Кешувати config/routes/views/events на етапі збірки
- `opcache.validate_timestamps=0` у продакшні

---

## 12. Health checks та K8s проби

### Конфігурація проб Kubernetes

```yaml
containers:
  - name: app
    startupProbe:
      httpGet:
        path: /up
        port: 8000
      initialDelaySeconds: 5
      periodSeconds: 5
      failureThreshold: 30    # 30 * 5с = 150с максимум на запуск
    livenessProbe:
      httpGet:
        path: /up
        port: 8000
      initialDelaySeconds: 10
      periodSeconds: 15
      timeoutSeconds: 5
      failureThreshold: 3
    readinessProbe:
      httpGet:
        path: /up
        port: 8000
      initialDelaySeconds: 5
      periodSeconds: 10
      timeoutSeconds: 3
      failureThreshold: 2
```

### Вбудована перевірка RoadRunner

```yaml
# .rr.yaml
status:
  address: "0.0.0.0:2114"
health:
  address: "0.0.0.0:2115"
```

Використовуйте порт 2115 для readiness-проби, 2114 для liveness-проби.

| Драйвер | Механізм перевірки | Рекомендована ціль проби |
|---------|-------------------|-------------------------|
| FrankenPHP | Laravel-маршрут `/up` | `httpGet` на порт 80/443 |
| Swoole | Laravel-маршрут `/up` | `httpGet` на порт застосунку |
| RoadRunner | Вбудований health-плагін + `/up` | Health RR для readiness, `/up` для liveness |

---

## 13. Коректне завершення та SIGTERM

### FrankenPHP (відома проблема)

**Баг:** При отриманні SIGTERM `frankenphp-worker.php` НЕ отримує сигнал. Він зависає в `frankenphp_handle_request()`. Контейнер завершується з кодом 137 (SIGKILL) замість 0. (GitHub issues #469, #970)

**Обхідне рішення:**
```yaml
spec:
  terminationGracePeriodSeconds: 45
  containers:
    - lifecycle:
        preStop:
          exec:
            command: ["/bin/sh", "-c", "sleep 5 && php artisan octane:stop"]
```

### Swoole

Краща обробка сигналів. Головний процес координує завершення воркерів. Воркери завершують поточні запити.

```ini
; php.ini
swoole.max_wait_time = 15
```

### RoadRunner

Найкраща обробка сигналів. Налаштовуваний grace period:

```yaml
# .rr.yaml
endure:
  grace_period: 30s
```

### Поведінка сигналів

| Сигнал | Swoole | RoadRunner | FrankenPHP |
|--------|--------|------------|------------|
| SIGTERM | Чекає активні з'єднання | Коректне через Go | Баг — зависає |
| SIGUSR1 | Перезавантажує воркерів | Н/Д | Н/Д |
| SIGINT | Негайне завершення | Негайне завершення | Негайне завершення |

### Універсальна найкраща практика

```yaml
spec:
  terminationGracePeriodSeconds: 60
  containers:
    - lifecycle:
        preStop:
          exec:
            command: ["/bin/sh", "-c", "sleep 10 && php artisan octane:stop"]
```

10-секундна пауза гарантує, що под повністю вилучений з endpoints Service перед початком завершення.

---

## 14. Розгортання без простою

### Відома проблема

Octane не слідкує за символічними посиланнями (GitHub #1004). Інструменти розгортання через симлінки (Deployer, Envoyer) ламаються, бо Octane пам'ятає директорію запуску. **Це НЕ проблема в Kubernetes** — кожен под отримує свіжий контейнер.

### Стратегія Rolling Update для K8s

```yaml
spec:
  replicas: 3
  strategy:
    type: RollingUpdate
    rollingUpdate:
      maxSurge: 1
      maxUnavailable: 0    # Ніколи не зменшувати нижче бажаної кількості
```

- `maxUnavailable: 0` — старий под живе, поки новий не готовий
- `readinessProbe` контролює трафік до нових подів
- `preStop` пауза дозволяє дерегістрацію з балансувальника
- `terminationGracePeriodSeconds` > `preStop sleep` + максимальна тривалість запиту

### Zero-downtime в Docker Compose

1. `docker compose up -d --no-deps --build app`
2. Використовувати зворотний проксі (Traefik) з перевіркою health
3. Тимчасово масштабувати: `docker compose up -d --scale app=2`, потім повернути

### PodDisruptionBudget

```yaml
apiVersion: policy/v1
kind: PodDisruptionBudget
metadata:
  name: laravel-octane-pdb
spec:
  minAvailable: 2
  selector:
    matchLabels:
      app: laravel-octane
```

| Реплік | minAvailable | maxUnavailable |
|--------|-------------|----------------|
| 2 | 1 | 1 |
| 3 | 2 | 1 |
| 5+ | 3 | 2 |

---

## 15. Горизонтальне масштабування

### Розподіл ресурсів на под

| Ресурс | Консервативний | Стандартний | Високе навантаження |
|--------|---------------|------------|-------------------|
| CPU request | 250m | 500m | 1000m |
| CPU limit | 500m | 1000m | 2000m |
| Memory request | 256Mi | 512Mi | 1Gi |
| Memory limit | 512Mi | 1Gi | 2Gi |
| Воркери | 2 | 4 | 8 |
| Max requests | 500 | 500 | 1000 |

### Команда запуску

```bash
php artisan octane:start \
    --server=frankenphp \
    --host=0.0.0.0 \
    --port=8000 \
    --workers=4 \
    --task-workers=2 \
    --max-requests=500
```

---

## 16. Захист від OOM Killer

### Рівні захисту

**Рівень 1 — `--max-requests`:** Воркери перезапускаються після N запитів. Перша лінія оборони.

**Рівень 2 — Поріг збирання сміття:**
```php
'garbage' => 128, // Примусовий GC при перевищенні 128 МБ
```

**Рівень 3 — PHP `memory_limit`:** Встановити нижче за ліміт контейнера:
```ini
memory_limit = 384M  # Ліміт контейнера 512Mi
```

**Рівень 4 — Ліміт пам'яті K8s:**
```yaml
resources:
  limits:
    memory: "768Mi"  # 50% запасу над очікуваним споживанням
```

**Рівень 5 — Увімкнути listener `CollectGarbage`:**
```php
OperationTerminated::class => [
    CollectGarbage::class,
],
```

**Рівень 6 — Супервізор RoadRunner:**
```yaml
http:
  pool:
    supervisor:
      max_worker_memory: 128  # МБ — вбиває воркер при перевищенні
```

---

## 17. Логування в контейнерах

### Правило: тільки stdout/stderr

```env
LOG_CHANNEL=stderr
```

### Специфіка драйверів

**FrankenPHP/Caddy:**
```caddyfile
{
    log {
        output stdout
        format json
    }
}
```

**RoadRunner (.rr.yaml):**
```yaml
logs:
  mode: production
  level: warn
  encoding: json
  output: stdout
```

**Swoole:** Логи йдуть у stdout/stderr за замовчуванням.

### Важливо

Якщо використовуєте supervisord, змініть цілі логування:
```ini
logfile=/dev/stdout
logfile_maxbytes=0
```

Для продакшн K8s: запускайте Octane напряму як PID 1 (не через supervisord), щоб він коректно отримував сигнали.

---

## 18. Ефемерна файлова система та сховище

Файлові системи контейнерів ефемерні. Потрібно налаштувати:

```env
FILESYSTEM_DISK=s3
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### Що потребує зовнішнього сховища

| Що | Рішення |
|----|---------|
| Завантаження користувачів | S3/MinIO/GCS |
| Згенеровані файли (експорти, PDF) | S3 або спільний том |
| storage/logs | stderr-канал |
| storage/framework/cache | Redis |
| storage/framework/sessions | Redis або база даних |
| storage/framework/views | Прекомпільовані при збірці Docker |
| bootstrap/cache | Вбудовані в образ |

### Для spatie/laravel-medialibrary

```php
// config/media-library.php
'disk_name' => env('MEDIA_DISK', 's3'),
```

### Якщо S3 недоступний — використовувати PVC

```yaml
apiVersion: v1
kind: PersistentVolumeClaim
metadata:
  name: laravel-storage
spec:
  accessModes:
    - ReadWriteMany  # Потрібно для доступу з кількох подів
  resources:
    requests:
      storage: 10Gi
  storageClassName: efs-sc
```

---

## 19. Змінні оточення

Octane завантажується один раз і тримає застосунок у пам'яті. Змінні оточення зчитуються один раз при старті. Зміна змінних вимагає перезапуску пода.

`config:cache` запікає значення в `bootstrap/cache/config.php` — оточення фіксується на етапі збірки при кешуванні.

### Специфіка FrankenPHP

`SERVER_NAME` контролює адреси прослуховування Caddy:
```env
SERVER_NAME=:80              # Порт 80, без автоматичного HTTPS
SERVER_NAME=example.com      # Автоматичний HTTPS з Let's Encrypt
```

За SSL-термінуючим проксі:
```env
SERVER_NAME=:80
OCTANE_HTTPS=true
TRUSTED_PROXIES=*
```

### Паттерн ConfigMap/Secret для K8s

```yaml
apiVersion: v1
kind: ConfigMap
metadata:
  name: laravel-config
data:
  APP_ENV: production
  APP_DEBUG: "false"
  LOG_CHANNEL: stderr
  OCTANE_SERVER: frankenphp
  CACHE_STORE: redis
  SESSION_DRIVER: redis
---
apiVersion: v1
kind: Secret
metadata:
  name: laravel-secrets
type: Opaque
stringData:
  APP_KEY: "base64:your-key-here"
  DB_PASSWORD: "secret"
  JWT_SECRET: "secret"
```

```yaml
envFrom:
  - configMapRef:
      name: laravel-config
  - secretRef:
      name: laravel-secrets
```

---

## 20. SSL/TLS термінація

### Стратегія 1: Термінація на Ingress (рекомендовано для K8s)

```yaml
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: laravel-ingress
  annotations:
    cert-manager.io/cluster-issuer: letsencrypt-prod
spec:
  tls:
    - hosts:
        - api.example.com
      secretName: api-tls
  rules:
    - host: api.example.com
      http:
        paths:
          - path: /
            pathType: Prefix
            backend:
              service:
                name: laravel-octane
                port:
                  number: 8000
```

### Стратегія 2: Вбудований TLS у FrankenPHP (один сервер)

FrankenPHP (Caddy) обробляє автоматичний HTTPS з Let's Encrypt:
```env
SERVER_NAME=api.example.com
```

### Стратегія 3: Traefik (Docker Compose)

```yaml
services:
  traefik:
    image: traefik:v3.0
    command:
      - "--certificatesresolvers.letsencrypt.acme.email=you@example.com"
    ports:
      - "443:443"
  app:
    labels:
      - "traefik.http.routers.app.tls.certresolver=letsencrypt"
```

### Ротація TLS-сертифікатів

| Драйвер | Поведінка ротації |
|---------|------------------|
| FrankenPHP | Автоматична через Caddy ACME (гаряче перезавантаження) |
| Swoole | Потребує перезапуску |
| RoadRunner | Потребує перезапуску |

---

## 21. Конфігурація зворотного проксі

### Nginx (для Swoole/RoadRunner)

```nginx
upstream octane {
    server 127.0.0.1:8000;
    keepalive 32;
}

server {
    listen 80;
    server_name api.example.com;

    location / {
        proxy_pass http://octane;
        proxy_http_version 1.1;
        proxy_set_header Host $http_host;  # Включити порт!
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header Connection "";
        proxy_buffering off;
        proxy_read_timeout 300s;
    }
}
```

**Застереження (GitHub #235):** `proxy_set_header Host $host` НЕ включає порт. Використовуйте `$http_host`.

### FrankenPHP: проксі не потрібен

FrankenPHP І Є веб-сервером. Для K8s достатньо ingress controller. Якщо за проксі:

```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware) {
    $middleware->trustProxies(at: '*');
})
```

---

## 22. Повні маніфести Kubernetes

### Deployment

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: laravel-octane
spec:
  replicas: 3
  strategy:
    type: RollingUpdate
    rollingUpdate:
      maxSurge: 1
      maxUnavailable: 0
  selector:
    matchLabels:
      app: laravel-octane
  template:
    metadata:
      labels:
        app: laravel-octane
    spec:
      terminationGracePeriodSeconds: 60
      initContainers:
        - name: migrate
          image: registry.example.com/laravel-octane:latest
          command: ["php", "artisan", "migrate", "--force"]
          envFrom:
            - configMapRef:
                name: laravel-config
            - secretRef:
                name: laravel-secrets
      containers:
        - name: app
          image: registry.example.com/laravel-octane:latest
          command: ["php", "artisan", "octane:frankenphp"]
          args: ["--host=0.0.0.0", "--port=8000", "--workers=4", "--max-requests=500"]
          ports:
            - containerPort: 8000
          envFrom:
            - configMapRef:
                name: laravel-config
            - secretRef:
                name: laravel-secrets
          resources:
            requests:
              cpu: "500m"
              memory: "512Mi"
            limits:
              cpu: "1000m"
              memory: "768Mi"
          startupProbe:
            httpGet:
              path: /up
              port: 8000
            periodSeconds: 5
            failureThreshold: 30
          livenessProbe:
            httpGet:
              path: /up
              port: 8000
            initialDelaySeconds: 15
            periodSeconds: 20
            failureThreshold: 3
          readinessProbe:
            httpGet:
              path: /up
              port: 8000
            initialDelaySeconds: 5
            periodSeconds: 10
            failureThreshold: 2
          lifecycle:
            preStop:
              exec:
                command: ["/bin/sh", "-c", "sleep 10 && php artisan octane:stop"]
---
apiVersion: v1
kind: Service
metadata:
  name: laravel-octane
spec:
  selector:
    app: laravel-octane
  ports:
    - port: 80
      targetPort: 8000
  type: ClusterIP
```

### Queue Worker Deployment

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: laravel-queue
spec:
  replicas: 2
  template:
    spec:
      containers:
        - name: worker
          image: registry.example.com/laravel-octane:latest
          command: ["php", "artisan", "queue:work", "--tries=3", "--max-jobs=500", "--memory=256"]
          resources:
            limits:
              memory: "512Mi"
```

### CronJob для планувальника

```yaml
apiVersion: batch/v1
kind: CronJob
metadata:
  name: laravel-scheduler
spec:
  schedule: "* * * * *"
  jobTemplate:
    spec:
      template:
        spec:
          containers:
            - name: scheduler
              image: registry.example.com/laravel-octane:latest
              command: ["php", "artisan", "schedule:run"]
          restartPolicy: OnFailure
```

---

## 23. Docker Compose для продакшну

```yaml
services:
  app:
    build:
      context: ./
      dockerfile: docker/php/Dockerfile
    restart: always
    environment:
      COMMAND: php artisan octane:frankenphp --host=0.0.0.0 --port=80 --workers=4 --max-requests=500
    healthcheck:
      test: curl -sf http://localhost/up || exit 1
      interval: 10s
      timeout: 5s
      retries: 3
      start_period: 30s
    deploy:
      resources:
        limits:
          cpus: "2.0"
          memory: 1G

  workers:
    build:
      context: ./
      dockerfile: docker/php/Dockerfile
    restart: always
    command: php artisan queue:work --tries=3 --max-jobs=500 --memory=256
    deploy:
      replicas: 2
      resources:
        limits:
          memory: 512M

  schedule:
    build:
      context: ./
      dockerfile: docker/php/Dockerfile
    restart: always
    command: php artisan schedule:work
```

Ключові відмінності від розробки:
- Без монтування томів (код вбудований в образ)
- Без прапорця `--watch`
- Без Xdebug
- Встановлені ліміти ресурсів
- `restart: always`

---

## 24. HPA автомасштабування

### На основі CPU (найпростіший)

```yaml
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: laravel-octane-hpa
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: laravel-octane
  minReplicas: 3
  maxReplicas: 15
  metrics:
    - type: Resource
      resource:
        name: cpu
        target:
          type: Utilization
          averageUtilization: 70
  behavior:
    scaleUp:
      stabilizationWindowSeconds: 60
      policies:
        - type: Pods
          value: 2
          periodSeconds: 60
    scaleDown:
      stabilizationWindowSeconds: 300
      policies:
        - type: Pods
          value: 1
          periodSeconds: 120
```

### Особливості HPA для Octane

- Воркери обмежені на под (4 воркери = максимум 4 конкурентних запити). HPA на основі CPU може не встигнути відреагувати.
- Пам'ять НЕ є гарним показником для масштабування — пам'ять зростає між циклами `--max-requests`, створюючи хибні сигнали.
- CPU 70% — гарний поріг за замовчуванням.
- Зменшення має бути консервативним: `stabilizationWindowSeconds: 300`.
- Мінімум реплік: ніколи менше 2 для HA, краще 3.

### Метрики від RoadRunner

```yaml
# .rr.yaml
metrics:
  address: "0.0.0.0:2112"
```

---

## 25. Питання безпеки

### Витік даних запитів між користувачами

Ризик безпеки №1. Якщо зберігати дані користувача в синглтонах або статичних властивостях, Користувач B може побачити дані Користувача A.

**Чеклист аудиту:**
- Пошук `static $` та `static array` у `Modules/`
- Пошук синглтонів, що отримують `Request` в конструкторі
- Тест з `--workers=1 --max-requests=2`: запит як Користувач A, потім Користувач B, перевірка ізоляції

### Відмінності обробки CORS

- Swoole: middleware CORS може некоректно додавати заголовки до preflight `OPTIONS` (GitHub #240)
- FrankenPHP: конфігурація CORS і в Caddyfile, І в Laravel створює дублікати заголовків
- Конфігуруйте CORS ТІЛЬКИ в одному місці

### Конфігурація довірених проксі

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->trustProxies(at: '*');
})
```

Встановити `OCTANE_HTTPS=true` при термінації TLS на ingress.

### Безпека cookie/сесій

| Драйвер | Рекомендація |
|---------|-------------|
| `redis` | Рекомендовано — спільний між воркерами/серверами |
| `database` | Працює — трохи повільніше |
| `file` | Уникати — можливі race conditions |
| `array` | Ніколи в продакшні |

### `exit()` / `die()` / `dd()` вбивають воркери

Ніколи не використовуйте в Octane. Вони вбивають процес воркера повністю (GitHub #654).

---

## 26. Обмеження частоти запитів

Завжди використовуйте Redis для rate limiting з Octane:

```env
CACHE_STORE=redis
```

In-memory rate limiting працює лише в межах одного воркера, не глобально. Rate limiting через Swoole tables працює в межах одного сервера (непослідовний у мультисерверних розгортаннях).

Використовуйте middleware `ThrottleRequestsWithRedis` для оптимізованого Redis rate limiting.

---

## 27. Обробка таймаутів

| Драйвер | Конфігурація | Поведінка при таймауті | Відомі проблеми |
|---------|-------------|----------------------|----------------|
| Swoole | `max_execution_time` (30с за замовч.) | Вбиває ВЕСЬ серверний процес | Streaming-ендпоінти падають (GitHub #1015); таймаути syscall спричиняють 502 (#651) |
| RoadRunner | `max_execution_time` + `.rr.yaml` | Мав би завершити запит | Може не працювати (GitHub #504) |
| FrankenPHP | `max_execution_time` | Повертає HTTP 500 замість 408 | Неправильний статус-код (GitHub #949) |

**Критично для Swoole:** При перевищенні `max_execution_time` вбивається **ВЕСЬ сервер**. Один повільний запит роняє всі з'єднання.

**Рішення:**
- Виносити довготривалу роботу в черги
- Встановити адекватний таймаут для найповільнішого легітимного ендпоінту
- Використовувати таймаут зворотного проксі як додатковий захист

---

## 28. Обробка помилок та падіння воркерів

**Неперехоплені винятки по драйверах:**

| Драйвер | Поведінка |
|---------|----------|
| Swoole | Воркер вбивається, Supervisor перезапускає; усі запити цього воркера падають |
| RoadRunner | Воркер-процес падає, RR створює заміну; короткий провал потужності |
| FrankenPHP | Відомий краш з "Undefined array key 'msg'" (GitHub #803) |

Listener `WorkerErrorOccurred` включає `ReportException::class` та `StopWorkerIfNecessary::class` за замовчуванням.

---

## 29. Відмінності поведінки middleware

Класи middleware резолвляться один раз і перевикористовуються між запитами.

```php
// НЕБЕЗПЕЧНО в Octane
class TrackingMiddleware
{
    private array $events = []; // Росте між запитами!

    public function handle(Request $request, Closure $next): Response
    {
        $this->events[] = $request->path(); // Витікає
        return $next($request);
    }
}
```

**Виправлення:** Ніколи не зберігайте стан запиту у властивостях middleware. Скидайте властивості в `handle` перед використанням.

---

## 30. Кешування DNS-резолвінгу

DNS-резолвінг PHP кешується на рівні процесу. У довготривалих воркерах зміни DNS (перемикання бази, зміна IP сервісу K8s) не підхоплюються.

**Рішення:**
- `--max-requests` циклічно перезапускає воркерів
- Увімкнити listener `DisconnectFromDatabases` для чутливих до DNS середовищ
- Налаштувати коротші TTL для DNS

---

## 31. Моніторинг та спостережуваність

### Ключові метрики для відстеження

| Метрика | Навіщо |
|---------|--------|
| Споживання пам'яті воркерами | Виявити витоки до OOM |
| Кількість активних воркерів | Виявити падіння |
| Глибина черги запитів | Проблеми з потужністю |
| Тривалість запитів (p50, p95, p99) | Виявлення регресії продуктивності |
| Кількість з'єднань з БД | Витоки з'єднань |
| Частота перезапуску воркерів | Висока = нестабільність |

### Сумісність APM

| Інструмент APM | Swoole | RoadRunner | FrankenPHP |
|----------------|--------|------------|------------|
| Xdebug | Ні | Так | Так |
| Datadog | Ненадійно | Працює | Працює |
| New Relic | Ненадійно | Працює | Працює |
| OpenTelemetry | Ручне налаштування | Працює | Працює |
| Inspector.dev | Вбудована підтримка | Вбудована | Вбудована |

### Middleware моніторингу пам'яті

```php
Log::debug('Memory usage', [
    'current' => memory_get_usage(true),
    'peak' => memory_get_peak_usage(true),
    'path' => $request->path(),
]);
```

---

## 32. Чеклист міграції: PHP-FPM -> Octane

1. Пошук у кодовій базі `static` властивостей, `exit`, `die`, `dd`
2. Аудит усіх singleton-прив'язок на стан, специфічний для запиту
3. Перенести всі виклики `env()` в конфігураційні файли
4. Перемикнути драйвери сесій та кешу на Redis
5. Увімкнути `FlushUploadedFiles` при роботі із завантаженнями
6. Увімкнути `CollectGarbage` для управління пам'яттю
7. Тестувати з `--workers=1 --max-requests=2` для виявлення витоків стану
8. Протестувати всі сторонні пакети під Octane
9. Налаштувати моніторинг пам'яті з першого дня
10. Налаштувати `--max-requests` (починати з 500)
11. Встановити `APP_DEBUG=false`
12. Ніколи не використовувати `exit()`, `die()` чи `dd()`
13. Налаштувати PgBouncer для пулінгу з'єднань з БД
14. Налаштувати коректні health checks
15. Налаштувати коректне завершення (`preStop` hooks)

---

## 33. Конфігурація для кожного драйвера

### Продакшн-конфігурація Swoole

```php
// config/octane.php
'swoole' => [
    'options' => [
        'worker_num' => max(4, swoole_cpu_num() * 2),
        'task_worker_num' => swoole_cpu_num() * 2,
        'max_request' => 5000,
        'max_wait_time' => 60,
        'package_max_length' => 10 * 1024 * 1024,
        'buffer_output_size' => 10 * 1024 * 1024,
        'socket_buffer_size' => 128 * 1024 * 1024,
        'enable_reuse_port' => true,
        'open_http2_protocol' => true,
        'heartbeat_check_interval' => 60,
        'heartbeat_idle_time' => 600,
        'hook_flags' => SWOOLE_HOOK_ALL,
    ],
],
```

### Продакшн php.ini для FrankenPHP

```ini
memory_limit = 512M
zend.enable_gc = 1
opcache.enable = 1
opcache.validate_timestamps = 0
opcache.max_accelerated_files = 20000
opcache.memory_consumption = 256
```

### Продакшн .rr.yaml для RoadRunner

```yaml
version: '3'

rpc:
  listen: tcp://127.0.0.1:6001

server:
  command: "php artisan octane:start --server=roadrunner --host=0.0.0.0 --port=8000"
  relay: pipes

http:
  address: 0.0.0.0:8000
  max_request_size: 256
  access_logs: false
  middleware:
    - headers
    - gzip
    - static
  pool:
    num_workers: 0
    max_jobs: 500
    allocate_timeout: 60s
    destroy_timeout: 60s
    supervisor:
      watch_tick: 5s
      ttl: 3600
      idle_ttl: 10s
      max_worker_memory: 128
      exec_ttl: 60s
  static:
    dir: public
    forbid: [".php", ".htaccess"]

logs:
  mode: production
  level: warn
  encoding: json
  output: stderr

status:
  address: 0.0.0.0:2114

health:
  address: 0.0.0.0:2115

metrics:
  address: 0.0.0.0:2112

endure:
  grace_period: 30s
```

---

## 34. Джерела

### Офіційна документація
- [Документація Laravel Octane (12.x)](https://laravel.com/docs/12.x/octane)
- [Офіційна документація FrankenPHP](https://frankenphp.dev/docs/)
- [Довідник конфігурації RoadRunner](https://docs.roadrunner.dev/docs/general/config)

### GitHub Issues (з посиланнями)
- [#469 — Коректне завершення SIGTERM](https://github.com/laravel/octane/issues/469)
- [#970 — Подія WorkerStopped не викликається](https://github.com/laravel/octane/issues/970)
- [#1004 — Розгортання без простою](https://github.com/laravel/octane/issues/1004)
- [#949 — FrankenPHP таймаут повертає 500](https://github.com/laravel/octane/issues/949)
- [#1015 — Swoole таймаут стрімів](https://github.com/laravel/octane/issues/1015)
- [#504 — max_execution_time RoadRunner не працює](https://github.com/laravel/octane/issues/504)
- [#654 — exit() вбиває воркер](https://github.com/laravel/octane/issues/654)
- [#803 — Краш FrankenPHP](https://github.com/laravel/octane/issues/803)
- [#481 — Витік пам'яті фасаду Http](https://github.com/laravel/octane/issues/481)
- [#240 — CORS зі Swoole](https://github.com/laravel/octane/issues/240)
- [#321 — Завантаження файлів Swoole](https://github.com/laravel/octane/issues/321)
- [#350 — Важкі завантаження файлів](https://github.com/laravel/octane/issues/350)
- [#442 — package_max_length захардкоджений](https://github.com/laravel/octane/issues/442)
- [#285 — MySQL server has gone away](https://github.com/laravel/octane/issues/285)
- [#963 — Витік пам'яті](https://github.com/laravel/octane/issues/963)
- [#887 — Витік ViewServiceProvider](https://github.com/laravel/octane/issues/887)
- [#222 — Проблема JWT-auth з Octane](https://github.com/PHP-Open-Source-Saver/jwt-auth/issues/222)
- [#56652 — Витік пам'яті APP_DEBUG](https://github.com/laravel/framework/issues/56652)
- [#47958 — Статичні властивості](https://github.com/laravel/framework/discussions/47958)
- [FrankenPHP #1821 — Повільність/зависання](https://github.com/php/frankenphp/issues/1821)
- [FrankenPHP #1066 — Ліміт пам'яті](https://github.com/php/frankenphp/issues/1066)

### Ресурси спільноти
- [Від нуля до 35М: масштабування Laravel з Octane](https://www.galahadsixteen.com/blog/from-zero-to-35m-the-struggles-of-scaling-laravel-with-octane)
- [Найкращі практики Laravel Octane (michael-rubel)](https://github.com/michael-rubel/laravel-octane-best-practices)
- [Laravel Octane: 15 просунутих технік конфігурації](https://dev.to/arasosman/laravel-octane-15-advanced-configuration-techniques-for-maximum-performance-5974)
- [Swoole проти RoadRunner для Laravel Octane](https://chriswhite.blog/coding/swoole-vs-roadrunner-for-laravel-octane/)
- [Laravel 12 — битва продуктивності: FrankenPHP vs RoadRunner](https://www.linkedin.com/pulse/laravel-12-performance-face-off-2025-frankenphp-roadrunner-roque-4rnrf)
- [Порівняння серверів PHP-застосунків 2025](https://www.deployhq.com/blog/comparing-php-application-servers-in-2025-performance-scalability-and-modern-options)
- [Продакшн Laravel з Traefik та FrankenPHP](https://hackernoon.com/laravel-at-6ms-production-ready-stack-with-traefik-octane-and-frankenphp)
- [exaco/laravel-docktane](https://github.com/exaco/laravel-docktane)
