# Backend Implementation Plan: Виправлення відображення завантажених зображень

## Scope
Бекенд/інфраструктурний аспект задачі `fix-uploaded-images-not-displaying`. Frontend (React/Inertia) виконує окремий агент — цей план документує контракт пропсів, але не чіпає `resources/js/**`.

## Root cause verification (проти кожної гіпотези з BA)

### #1 — Відсутній `public/storage` symlink — CONFIRMED, dominant cause
- `ls public/storage` → not found на хості (dev volume, спільний з контейнером `app`).
- `docker/php/Dockerfile` (prod) виконує лише `config:cache`/`route:cache`/`view:cache`/`event:cache` під час білду; ніде немає `artisan storage:link`.
- `docker/php/Dockerfile-dev` — так само, жодного виклику `storage:link`.
- `ENTRYPOINT` в обох Dockerfile — напряму `supervisord --nodaemon ...` (без обгортки-скрипта), тобто немає точки для рантайм-ініціалізації.
- `config/filesystems.php`: `links => [public_path('storage') => storage_path('app/public')]` — стандартна конфігурація, самого лінку ніхто не створює.
- Наслідок: `Media::getUrl()` формує `APP_URL/storage/...`, але фізичного шляху не існує → FrankenPHP віддає 404 для **оригіналу і всіх конверсій** (аватар, фото лічильника, фото показань). Це єдина причина, достатня для повного пояснення бага.
- Для prod-образу симлінк неможливо створити на етапі build і покласти у фінальний stage надійно для dev-варіанту (там `./:/var/www` монтується як volume і перекриває build-time зміни). Отже фікс має бути **runtime**, в entrypoint, а не в `RUN` під час білду.

### #2 — Черга конверсій ненадійна — CONFIRMED as real, але secondary і env-scoped
- `compose.yml` має окремий сервіс `workers` з `COMMAND: php artisan queue:work --sleep=3 --tries=3 --timeout=60` (без `--connection`/`--queue`) → слухає `config('queue.default')` = `QUEUE_CONNECTION` з `.env`.
- `config/media-library.php`: `'queue_connection_name' => env('MEDIA_QUEUE_CONNECTION', 'redis')` — конверсії диспатчаться на `MEDIA_QUEUE_CONNECTION`, дефолт `'redis'`.
- Коміт `92242bf` («Close gaps in background photo optimization pipeline») вже додав `MEDIA_QUEUE_CONNECTION=database` у `.env.example`, узгодивши його з `QUEUE_CONNECTION=database`. Це правильний намір.
- **Проблема**: локальний (gitignored) `.env` у цьому репозиторії має `QUEUE_CONNECTION=database`, але **не має** рядка `MEDIA_QUEUE_CONNECTION` → конверсії досі диспатчаться на `redis`-конекшн, а `workers`-контейнер слухає `database`. Це **environment drift одного дев-оточення**, не дефект коду в репозиторії: `.env.example` вже містить правильне значення з моменту коміту 92242bf, просто конкретний `.env` не був оновлений після pull.
- **Рішення в цьому плані**: НЕ редагувати `.env` (заборонено — файл gitignored і поза скоупом коду) і НЕ редагувати `config/media-library.php` (hard rule: не змінювати `config/*.php`, щоб «змусити фічу працювати»; дефолт `'redis'` — свідомий вибір, зміна цього дефолту зачепить усі оточення заради одного застарілого файлу). Замість цього:
  1. Документується як **required manual step** для debugger/QA/розробника: звірити `.env` з `.env.example` і додати `MEDIA_QUEUE_CONNECTION=database` (або відповідне значення для конкретного оточення).
  2. Виносжу питання «чи узгодити дефолт `queue_connection_name` з `queue.default` (тобто `null` замість хардкоду `'redis'`)?» як **Open Question для стейкхолдера** (розширення BA Open Question #3), а не самостійне рішення в цьому PR.
- Runtime-верифікація (застряглі jobs у Redis) — задача debugger/qa-engineer, не цього плану.

### #3 — Немає авто-refresh для аватара — CONFIRMED (frontend gap, no backend action)
- `Meters/Index.tsx` використовує `usePoll(4000, { only: ['meters'] })`; `ProfileTab.tsx` такого виклику не має. Це суто фронтенд-задача (інший агент). Бекенд-контракт (`is_processing`) вже готовий для реалізації поллінгу.

### #4 — Фото показань ніде не рендеряться — CONFIRMED functional gap, no backend action needed
- `MeterReadingResource::toAttributes()` вже повертає `photos[]` з повним конверсійним контрактом (див. нижче). Жодна сторінка фронтенду це не рендерить. Бекенд змін не потребує.

### #5 — HEIC fallback на оригінал — CONFIRMED as edge case, no immediate backend code change
- `resolveMediaConversionUrls()` завжди повертає `original_url`; коли `is_processing=false` фронтенд може впасти на нього. Якщо оригінал HEIC — браузер не покаже. Це UX-рішення фронтенду (чи взагалі не давати `original_url` як fallback для HEIC), контракту не змінює. Задокументовано як edge case нижче.

### Додатково перевірено (не було в ранжованому списку BA, але релевантно)
- **MIME-неузгодженість між моделями** — підтверджено кодом:
  - `User`/`UpdateUserRequest`: `jpeg,png,gif,heic,heif` (модель і request узгоджені між собою).
  - `Meter`/`Store|UpdateMeterRequest`: `jpeg,png,gif,heic,heif` (узгоджені).
  - `MeterReading`/`BatchMeterReadingRequest`: `jpg,jpeg,png,webp,heic,heif` (узгоджені).
  - Кожна пара модель+request внутрішньо консистентна (файл, що пройде валідацію, буде прийнятий і колекцією медіа) — це НЕ причина бага з відображенням. Це лише крос-модельна непослідовність (webp vs gif), що впливає на UX-повідомлення, не на функціональність. **Залишаю як задокументований follow-up, поза скоупом цього фіксу** (уникнення scope creep за вказівкою advisor).
- **Upload limits** вже узгоджені комітом `7210624` (15M/16M PHP-ліміти > 10MB max_file_size medialibrary > 2MB avatar UI-текст). Текст UI «до 2 МБ» для аватара збігається з `UpdateUserRequest`'s `max:2048` (KB) — коректно.
- **Тестовий розрив підтверджено**: `phpunit.xml` встановлює `QUEUE_CONNECTION=sync`, усі наявні тести (`ResolvesMediaConversionUrlsTest`, `HandleInertiaRequestsAvatarTest`, `MeterResourcePhotoShapeTest`, `MeterReadingResourcePhotosShapeTest`) використовують `Storage::fake('public')`. Тому й symlink-баг, і queue-mismatch баг НЕ ловляться жодним існуючим тестом — вони перевіряють форму URL, не реальну доступність байтів. Потрібен окремий retrievability-тест (реальний диск, реальний HTTP GET) — **це відповідальність qa-engineer/tester фази**, не цього плану; тут лише фіксується як risk.

## Files to create/modify

### Створити
- `docker/php/entrypoint.sh` — новий shell-скрипт, що на старті контейнера (лише для процесу `app`, щоб уникнути race conditions між `app`/`workers`/`schedule`/`websockets` контейнерами в dev, які спільно монтують `./:/var/www`) виконує `php artisan storage:link`, якщо симлінк ще не існує, і потім передає керування далі (`exec "$@"`).

  ```sh
  #!/bin/sh
  set -e

  if [ "$PROCESS" = "app" ]; then
      if [ ! -e /var/www/public/storage ]; then
          php artisan storage:link || true
      fi
  fi

  exec "$@"
  ```

### Модифікувати
- `docker/php/Dockerfile` (prod) — додати `COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh` + `RUN chmod +x /usr/local/bin/entrypoint.sh`; змінити:
  ```
  ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
  CMD ["supervisord", "--nodaemon", "--configuration", "/etc/supervisor/conf.d/supervisord.conf"]
  ```
  (замість жорсткого `ENTRYPOINT ["supervisord", ...]`).
- `docker/php/Dockerfile-dev` — те саме: копіювання/chmod entrypoint-скрипта + зміна `ENTRYPOINT`/`CMD` за тим самим патерном (шлях відносний, файл буде перекритий volume-монтуванням `./:/var/www`, тому копіюємо саме в `/usr/local/bin/`, поза `/var/www`, щоб volume mount його не приховав).
- `docs/plans/fix-uploaded-images-not-displaying/02-development-plan-backend.md` — цей файл (документація плану).

### НЕ модифікувати (свідомо, з обґрунтуванням)
- `.env` / `.env.example` — `.env.example` вже коректний (коміт 92242bf); `.env` — gitignored, поза скоупом коду, редагування залишити для debugger-фази/розробника локально.
- `config/media-library.php` — дефолт `queue_connection_name` не чіпаємо; питання виносимо як Open Question.
- `Modules/**/Models/*.php`, `Modules/**/Http/Requests/*.php` — MIME-набори консистентні всередині кожної пари модель+request; крос-модельна неузгодженість не спричиняє бага з відображенням, залишається follow-up.
- `Modules/Shared/Concerns/ResolvesMediaConversionUrls.php` та відповідні Resources (`UserResource`, `MeterResource`, `MeterReadingResource`) — вже коректні (перевірено кодом і тестом), контракт достатній.
- `Modules/Auth/Actions/GetUserAvatar.php`, `app/Http/Middleware/HandleInertiaRequests.php` — вже коректні (media eager-loaded через `loadMissing('media')`, умовний `avatar` через `when(relationLoaded(...))`).

## Implementation order
1. Написати `docker/php/entrypoint.sh`.
2. Оновити `docker/php/Dockerfile` (prod): COPY + chmod + ENTRYPOINT/CMD split.
3. Оновити `docker/php/Dockerfile-dev`: те саме.
4. Локально: `docker compose build app workers schedule websockets` (усі використовують той самий образ через `&app`/`<<: *app`), потім `docker compose up -d`, перевірити, що `public/storage` з'являється як симлінк у контейнері `app` (і на хості, бо volume спільний у dev).
5. Ручна dev-note (не файл у репо): звірити `.env` з `.env.example`, додати `MEDIA_QUEUE_CONNECTION=database`, якщо відсутнє — задокументувати в deliverable як required manual step, не автоматизувати змінами в коді.

## Key design decisions
1. **Runtime symlink creation через entrypoint, не build-time.** У dev-образі `./:/var/www` монтується як volume і перекриє будь-який симлінк, створений під час `docker build`. Єдиний надійний момент — старт контейнера, після монтування volume. Той самий скрипт покриває і prod-образ для консистентності (немає volume overlay в prod, але однаковий підхід простіший в підтримці).
2. **Guard `PROCESS=app`.** У `compose.yml` усі сервіси (`app`, `workers`, `schedule`, `websockets`) поділяють один образ і (у dev) один volume `./:/var/www`. Якщо запускати `storage:link` у кожному контейнері без розбору, у dev виникає race condition на спільному host-mounted volume (кілька процесів одночасно перевіряють/створюють симлінк). Обмеження до `PROCESS=app` — сервісу, що фактично віддає HTTP-запити — усуває гонку, залишаючись достатнім (симлінк спільний для всіх контейнерів через volume у dev; у prod кожен контейнер має власний FS, але лише `app`-контейнер обслуговує публічні файли через FrankenPHP).
3. **`|| true` після `storage:link`.** Навіть з guard і pre-check (`[ ! -e ... ]`), гранично малий race (наприклад рестарт контейнера одночасно з іншим) не повинен валити запуск застосунку — краще no-op лог, ніж контейнер, що не піднявся через ідемпотентну операцію.
4. **Не редагувати `.env`/`config/media-library.php` для queue-mismatch.** Дотримання hard rule агента; коренева причина №2 — застаріле локальне середовище, не дефект коду в репозиторії (comparable `.env.example` вже правильний з коміту 92242bf). Зміна дефолту в конфізі зачепила б усі оточення заради одного стейл-файлу — ризик регресії без бізнес-схвалення.
5. **MIME-консистентність між моделями залишена як follow-up, не фікс.** Кожна пара модель+валідація вже внутрішньо коректна; об'єднання наборів (approve webp+gif скрізь) — окрема, узгоджена зі стейкхолдером зміна поведінки завантаження, не частина «фото не відображаються».

## Skills to invoke
- `php-foundation:php-conventions`
- `php-foundation:php-testing` (для наступної qa/tester фази, орієнтир)
- `laravel:laravel-conventions`
- `laravel:eloquent-patterns`
- `medialibrary-development`

## Risks and edge cases
- **Race в dev на shared volume**: покрито guard `PROCESS=app` + ідемпотентна перевірка існування симлінка перед викликом артизан-команди.
- **Prod deployment без persistent volume для `storage/app/public`**: якщо контейнер перезапускається на новому хості/pod без persistent storage, `storage:link` спрацює знову при кожному старті — безпечно, ідемпотентно, але самі файли (оригінали/конверсії) втрачаються, якщо диск не persistent. Це інфраструктурне питання поза скоупом цього фіксу (потребує S3/persistent volume — не зачіпаємо, MEDIA_DISK лишається `public` за замовчуванням).
- **Queue mismatch (#2) лишається невиправленим кодово** — якщо стейкхолдер не підтвердить локальний `.env`-фікс, конверсії продовжать «висіти» навіть після виправлення symlink. Це явно позначено як known follow-up, не приховане.
- **HEIC fallback на `original_url`** — може дати непридатне для браузера зображення, поки `is_processing=true` не оброблено фронтендом обережно (фронтенд-агент має уникати рендеру `original_url` як fallback, якщо MIME HEIC/HEIF — потребує або розширення контракту (`mime_type` у відповіді), або консервативного UX (спінер до готовності, без HEIC-fallback)). **Рекомендація фронтенд-агенту**: не покладатись на `original_url` як universal fallback.
- **CI/pipeline**: не знайдено `.github`/CI-конфігурації в репозиторії — якщо CI існує поза цим репо (окремий раннер), варто перевірити, чи build-образ там теж використовує оновлені Dockerfile (за замовчуванням — так, бо ENTRYPOINT змінюється в самому Dockerfile).

## Inertia Props Contract (без змін — підтверджено достатнім)
- **`auth.user` (shared prop, `HandleInertiaRequests`)**: `UserResource` → `data.attributes.avatar: { original_url: string, optimized_url: string|null, thumbnail_url: string|null, is_processing: boolean } | null` (омиться повністю, якщо media relation не завантажена — на практиці завжди завантажена через `loadMissing('media')`).
- **`Meters/Index`, `Meters/Edit` (`meter` prop через `MeterResource`)**: `data.attributes.photo: { original_url, optimized_url, thumbnail_url, is_processing } | null` (той самий формат, `resolveMediaConversionUrls` повертає `null` лише якщо немає media взагалі).
- **`Readings/*` (`MeterReadingResource`, множинна колекція `photos`)**: `data.attributes.photos: Array<{ id: number, original_url: string, optimized_url: string|null, thumbnail_url: string|null, is_processing: boolean }>`. **Бекенд змін не потребує для Story 3** — фронтенд-агент реалізує рендер на основі вже наявного контракту.

## Known follow-ups for next phases
- **debugger/qa-engineer**: верифікувати runtime — після фіксу symlink, підтвердити реальний HTTP GET 200 на `original_url`/`optimized_url`/`thumbnail_url` для всіх трьох моделей; перевірити застряглі jobs у Redis через `MEDIA_QUEUE_CONNECTION` mismatch (root cause #2) і чи є вони в поточному оточенні.
- **розробник (ручна дія, поза PR)**: звірити локальний `.env` з `.env.example`, додати `MEDIA_QUEUE_CONNECTION=database` (або значення, узгоджене з `QUEUE_CONNECTION` цього оточення).
- **stakeholder decision needed**: чи змінювати дефолт `queue_connection_name` у `config/media-library.php` з `'redis'` на `null` (auto-match `queue.default`), щоб унеможливити цей клас багів системно — окреме рішення, не частина цього фіксу.
- **inertia-react-architect**: реалізувати рендер `MeterReading.photos[]` (Story 3, функціональний пробіл — сторінки показань), додати `usePoll` для `ProfileTab` за аналогією з `Meters/Index.tsx` (Story 1), обробити `is_processing` спінер-стан і уникати HEIC `original_url` fallback (Story 6/edge case).
- **tester/qa-engineer**: написати реальний retrievability-тест (без `Storage::fake`), що перевіряє фактичний HTTP GET на URL медіа — існуючі тести цю прогалину не покривають.
- **poза скоупом, задокументовано, не виправлено**: крос-модельна MIME-неузгодженість (webp vs gif) — потребує окремого рішення стейкхолдера щодо офіційно підтримуваних форматів (BA Open Question #3).
