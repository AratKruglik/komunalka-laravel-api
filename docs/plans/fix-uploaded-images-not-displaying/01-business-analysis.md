# Business Analysis: Виправлення відображення завантажених зображень (аватар, фото лічильників, фото показань)

## Executive summary
Завантажені зображення (аватар користувача, фото лічильника, фото показань) не відображаються в UI. Ключовий висновок аналізу: гіпотеза з брифу («ймовірно, не обробляються та не зберігаються у файловій системі») лише частково вірна — оригінали, найімовірніше, зберігаються (`addMedia()->toMediaCollection()` викликається у робочих Actions), але недоступні для показу. Проблема лежить у площині доступності/віддачі файлів та відображення, а не у збереженні. Завдання — верифікувати pipeline end-to-end та усунути системну причину (найімовірніше — відсутній `storage:link` symlink та/або незавершені queued-конверсії), а також закрити функціональний пробіл: збережені фото показань ніде не рендеряться.

## Джерела вимог
- **_brief.md** — єдине джерело від стейкхолдера (укр.): аватар + фото лічильників + фото показань не відображаються; перевірити обробку/збереження; покращити UI показу зображень (skill `ui-ux-max`).
- **Кодова база** (валідація вимог) — див. розділ «Codebase impact».
- **docs/ADR.md — ADR-006** — рішення про `spatie/laravel-medialibrary`, queued conversions (optimized 800px / thumbnail 200px, jpg 85%), imagick для HEIC.
- **Контекст git** — гілка `fix/uploaded-images-show-fix`, недавні коміти «Close gaps in background photo optimization pipeline», «photo-background-processing», «Raise PHP upload limits». Це вказує на те, що pipeline конверсій — активна зона проблеми/незавершеного фіксу.

## Functional requirements
1. Завантажений аватар користувача має відображатися в UI (topbar, sidebar, ProfileTab) одразу після завершення обробки, без ручних дій.
2. Завантажене фото лічильника має відображатися у списку/картці лічильника (`MeterCard`) та на сторінці редагування.
3. Завантажені фото показань (`MeterReading.photos`) мають відображатися користувачу (наразі — жодна сторінка їх не рендерить; бекенд уже віддає їх у `MeterReadingResource`).
4. Оригінал зображення має бути доступний за URL (HTTP 200 + коректний `Content-Type`), а не лише формуватися як рядок.
5. Черга конверсій (`optimized`, `thumbnail`) має надійно виконуватися; після завершення обробки `is_processing` стає `false`, і UI показує зображення без перезавантаження сторінки вручну.
6. Стан «в обробці» (`is_processing = true`) має давати коректний UX-плейсхолдер (спінер), а не «зламаний» `<img>`; після готовності — показ зображення (skill `ui-ux-max`).
7. Формати HEIC/HEIF (мобільні фото) мають коректно доводитися до відображуваного вигляду (браузери не рендерять HEIC напряму — потрібна конверсія в jpg).

## Non-functional requirements
- **Performance**: конверсії виконуються у черзі (Redis) поза HTTP-циклом; UI використовує `thumbnail`/`optimized` замість оригіналу для списків; поллінг припиняється, коли немає елементів в обробці.
- **Security**: доступ до медіа має поважати авторизацію (Policies) — користувач бачить лише свої фото; публічний диск не повинен розкривати чужі файли через передбачувані шляхи (перевірити).
- **Compliance**: при видаленні користувача/лічильника/показання пов'язані медіа мають видалятися (medialibrary робить це автоматично при видаленні моделі — верифікувати для GDPR-видалення).

## User stories (Gherkin)

### Story 1: Відображення аватара
**As a** авторизований користувач
**I want** бачити свій завантажений аватар у застосунку
**So that** я впевнений, що фото збережено та профіль персоналізовано

**Acceptance criteria:**
- Given користувач завантажив аватар і конверсії згенеровано, When відкриває будь-яку сторінку, Then аватар відображається в topbar/sidebar/ProfileTab.
- Given конверсії ще в обробці, When рендериться ProfileTab, Then показується стан обробки (спінер), а не зламане зображення.
- Given конверсії завершено під час сесії, When застосунок отримує оновлені дані, Then аватар з'являється без ручного перезавантаження.

### Story 2: Фото лічильника у списку
**As a** користувач
**I want** бачити мініатюру фото лічильника в картці
**So that** візуально ідентифікувати лічильник

**Acceptance criteria:**
- Given лічильник має фото з готовими конверсіями, When відкривається список лічильників, Then у `MeterCard` показано `optimized`/`thumbnail`.
- Given фото в обробці, When рендериться картка, Then показано індикатор обробки; список поллиться і оновлюється по готовності.

### Story 3: Фото показань (функціональний пробіл)
**As a** користувач
**I want** переглядати фото, прикріплені до показань
**So that** мати документальне підтвердження знятих показань

**Acceptance criteria:**
- Given показання має прикріплені фото, When користувач переглядає показання (список/деталь), Then фото відображаються.
- Given бекенд уже віддає `photos[]` у `MeterReadingResource`, When реалізується UI, Then НЕ потрібні зміни бекенду/схеми — лише фронтенд-відображення.

### Story 4: Доступність файлу за URL
**As a** розробник/тестувальник
**I want** щоб URL зображення реально віддавав байти
**So that** зображення не «ламалися» через відсутню інфраструктуру віддачі

**Acceptance criteria:**
- Given медіа збережено, When виконується HTTP GET на `original_url`, Then відповідь 200 з image-`Content-Type`.
- Given згенеровано конверсію, When GET на `optimized_url`/`thumbnail_url`, Then 200 з зображенням.

### Story 5: Надійність черги конверсій
**As a** користувач
**I want** щоб оптимізовані версії створювалися завжди
**So that** зображення врешті відображалися, а не «зависали» в обробці назавжди

**Acceptance criteria:**
- Given фото завантажено, When queue worker обробляє чергу, Then протягом розумного часу у сховищі з'являються файли конверсій і `is_processing` стає `false`.
- Given worker недоступний, Then система має деградувати передбачувано (показ оригіналу як fallback / явний стан помилки) — потребує рішення (див. Open questions).

## Data model sketch
**Змін не потрібно.** Медіа зберігаються у polymorphic-таблиці `media` (spatie/laravel-medialibrary). Колекції: `User.avatar` (singleFile), `Meter.photo` (singleFile), `MeterReading.photos` (множинна). Конверсії `optimized`/`thumbnail` реєструються на всіх трьох моделях. Схема адекватна вимогам.

## API contract sketch
**Змін не потрібно.** Наявні шляхи:
- Аватар віддається через shared Inertia props (`auth.user.data.attributes.avatar`) на базі `UserResource` + `ResolvesMediaConversionUrls`; існує також окремий route-action `GetUserAvatar` (`response()->file($media->getPath($conversion))`).
- `MeterResource.photo`, `MeterReadingResource.photos[]` уже містять `original_url/optimized_url/thumbnail_url/is_processing`.
- Завантаження: `UploadMeterPhoto`, `CreateBatchReadings` (photos), `UpdateUser` (avatar).
Контракт достатній; фікс — інфраструктурний/фронтендовий, не контрактний.

## Ранжовані гіпотези root cause (для debug + dev фази; перевірено кодом, не запуском)
1. **[Системна, найімовірніша] Відсутній `public/storage` symlink.** `php artisan storage:link` не викликається у жодному Docker-бутстрапі (`docker/php/Dockerfile` робить лише config/route/view/event cache; entrypoint не містить storage:link). На хості symlink `public/storage` відсутній. Диск `public` віддає URL `APP_URL/storage/...`; без symlink FrankenPHP/Octane повертає 404 для ВСІХ медіа (оригінал і конверсії). Пояснює широку відмову.
2. **[Системна] Черга конверсій не виконується/не завершується.** «Configured in compose.yml» ≠ «running & succeeding». Гілка `fix/...-show-fix` та коміти про «gaps in background photo optimization pipeline» вказують, що це активна зона. Якщо worker не працює або конверсії падають (imagick/HEIC), `is_processing` лишається `true` назавжди; `resolveMediaSrc` свідомо повертає `null` під час обробки → вічний спінер.
3. **[UX, вторинна] Немає авто-оновлення після завантаження для аватара/показань.** `Meters/Index` має `usePoll(4000, {only:['meters']})`. Для аватара (`ProfileTab`) поллер не знайдено — коментар у коді покладається на «poll/reload», якого немає → щойно завантажений аватар не з'явиться в межах сесії, навіть якщо все інше працює.
4. **[Функціональний пробіл] Фото показань не рендеряться ніде.** `MeterReadingResource` віддає `photos[]`, але `Readings/Index.tsx` та інші сторінки не показують збережені фото (`ReadingCard`/`Create.tsx` показують лише локальний прев'ю до завантаження).
5. **[Edge] HEIC-оригінал як fallback не відображається.** `resolveMediaSrc` fallback-ає на `original_url`, коли `is_processing=false`; якщо оригінал HEIC, браузер не покаже його. Fallback безпечний лише для jpg/png-оригіналів.

**Дискримінуючі перевірки для debugger'а:** (а) GET `original_url` у браузері → 200? (symlink); (б) файли конверсій з'являються в `storage/app/public` за хвилину після завантаження? (черга); (в) ручний reload показує зображення? (поллінг/refresh).

## Edge cases & error scenarios
- Worker черги вимкнено → зображення «зависає» в обробці; потрібен fallback або моніторинг.
- HEIC/HEIF оригінал без готової jpg-конверсії → зламаний `<img>`.
- Часткова готовність конверсій (`optimized` є, `thumbnail` нема) → `is_processing=true`, поведінка UI має бути коректною (покрито unit-тестом concern).
- Гонка: користувач замінює аватар (singleFile) поки стара конверсія ще в черзі.
- Видалення моделі під час обробки медіа (orphan-файли/jobs).
- Ліміти завантаження PHP/nginx (недавно піднято) vs `media_library.max_file_size` (10MB) vs UI-текст «до 2 МБ» для аватара — неузгодженість повідомлень.
- Неузгодженість MIME: `Meter`/`User` приймають `gif` але не `webp`; `MeterReading` приймає `webp` але не `gif`.
- Прод vs dev: у прод-образі symlink та кеш поводяться інакше — перевірити обидва середовища.

## Risks & dependencies
- Залежність від Redis queue worker (compose має `queue:work`) та imagick (HEIC) у контейнері.
- Ризик «зелених» тестів при реальному багу: усі наявні тести (`HandleInertiaRequestsAvatarTest`, `MeterResourcePhotoShapeTest`, `MeterReadingResourcePhotosShapeTest`, `ResolvesMediaConversionUrlsTest`) використовують `Storage::fake('public')` — вони перевіряють ФОРМУ URL, а не те, що URL віддає байти. Саме тому баг проходить CI. Потрібен реальний end-to-end retrievability-тест.
- Ризик Octane + `queue_conversions_after_database_commit=true`: перевірити коректність у контексті довгоживучих воркерів/транзакцій.
- Залежність від skill `ui-ux-max` для покращення UI показу.

## Open questions for stakeholders
1. Яка бажана поведінка fallback, коли конверсії ще не готові або worker недоступний — показувати оригінал (ризик HEIC), спінер, чи повідомлення про помилку?
2. Чи потрібне авто-оновлення (poll) для аватара та показань за аналогією з `Meters/Index`, чи достатньо оновлення при наступній навігації?
3. Який максимальний розмір/формати офіційно підтримуємо (узгодити UI-текст «2 МБ», `max_file_size` 10MB, PHP-ліміти, MIME-набори між моделями)?

## Estimated complexity
medium
