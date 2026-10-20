# TODO: Міграція UI на дизайн-систему ElevenLabs

Повний план: [docs/plans/design-system-elevenlabs-migration/plan.md](plans/design-system-elevenlabs-migration/plan.md)

## Етап 0 — Збереження плану
- [x] Скопіювати план у `docs/plans/design-system-elevenlabs-migration/plan.md`
- [x] Створити цей todo-лист

## Етап 1 — Токен-шар + шрифт (фундамент)
- [x] Оновити `@theme` у `resources/css/app.css`: нові значення + перейменовані токени + alias для старих імен
- [x] Dark override блок: OLED `#000000`, поверхні `#1A1A1A`/`#252525`, бордер `#2E2E2E`
- [x] `--font-sans: 'Inter', system-ui, sans-serif`
- [x] `app.blade.php`: `<link>` Google Fonts Inter + inline no-flash скрипт (`data-theme` з cookie до першого кадру)
- [x] Чекпойнт: `yarn build`, візуальна перевірка light/dark

## Етап 2 — Shared UI (`Components/ui/*`, `styles/*`)
- [x] `Button.tsx`: variant `primary | secondary | ghost | danger`, прибрати tone/violet
- [x] Мігрувати всі виклики `<Button tone=... />` на новий API
- [x] Card, Badge, Alert, ConfirmDialog, DropdownMenu, Spinner, form/*, FormField, Logo, UserAvatar — токени замість хардкоду
- [x] Звести `styles/card.ts|iconContainer.ts|navItem.ts`, прибрати дубль Card (видалено мертвий card.ts/navItem.ts)
- [x] Прибрати токени `secondary`(violet)/`accent`
- [x] Чекпойнт: `tsc --noEmit` чисто; `yarn build` не вдалося прогнати локально (відсутній native-біндінг rolldown, Docker не запущений) — прогнати в Docker пізніше

## Етап 3 — Навігація + Лейаути
- [x] `AuthenticatedLayout.tsx`, `GuestLayout.tsx`
- [x] `AuthenticatedSidebar.tsx` (240px, OLED dark bg, active nav — золото/text-inverse), `AuthenticatedTopbar.tsx`, `Breadcrumbs.tsx`, `ThemeToggle.tsx`
- [x] Чекпойнт: `tsc --noEmit` чисто, grep хардкоду чистий; `yarn build` — прогнати в Docker пізніше

## Етап 4 — Сторінки + партіали (~52 файли)
- [x] Auth: Login/Register/ForgotPassword/ResetPassword (переписані на shared-компоненти Input/Label/Button/PasswordInput/GoogleIcon/GithubIcon)
- [x] Dashboard + партіали (WelcomeHeader, ConsumptionChart, ExpenseDistribution, RecentReadingsTable, QuickActions) — UTILITY_COLORS/LABELS винесено в `constants/utilityColors.ts`, chart chrome (grid/axis/tooltip) адаптовано під тему через CSS vars
- [x] Addresses (Index/Create/Edit + AddressCard/AddressForm) — виправлено контраст тексту на золотому фоні (text-inverse)
- [x] Meters (Index/Create/Edit + MeterCard/MeterForm)
- [x] Providers (Index/Create/Edit + ProviderForm) — off-brand yellow/amber замінено на primary-токени
- [x] Readings (Index/Create + ReadingCard/ReadingSummaryTable)
- [x] Settings (усі таби + SettingsSidebar/ThemeCard/ConnectedAccountCard) — active tab = золото/text-inverse
- [x] Фінальний grep старих утиліт → 0 посилань → alias-блоки видалено з app.css (light + dark), прибрано невживані primary-light/bg/active, error-dark
- [x] Чекпойнт: `tsc --noEmit` чисто по всьому проєкту; `yarn build` — не вдалося прогнати локально (відсутній native-біндінг rolldown, Docker не запущений), прогнати в Docker

## Верифікація (перед завершенням)
- [x] `docker compose exec app php artisan test` — 447 тестів, усі зелені (включно з tests/Browser/)
- [x] Скріншоти (chrome-devtools MCP) Login/Register/Dashboard/Settings у light і dark — відповідають DESIGN.md
- [x] Grep підтверджує 0 старих утиліт/токенів
- [x] Ручна звірка з `docs/DESIGN.md`: золото #F5C542, OLED-фон #000000, Inter, контраст, active nav = золото+text-inverse

## Review

### Підсумок
Повна міграція UI на дизайн-систему ElevenLabs виконана за 4 етапи (токени → shared UI → навігація/лейаути → сторінки), плюс фінальна верифікація в реальному браузері через Docker + chrome-devtools MCP.

### Знайдені та виправлені по дорозі баги (не з провини міграції, але виявлені під час QA)
1. **No-flash скрипт не обробляв `system` тему** (Етап 1) — при системній темній темі був спалах світлим до React-гідратації. Виправлено читання `prefers-color-scheme` у скрипті.
2. **`Dashboard/Index.tsx` ніколи не мав обгортки `<AuthenticatedLayout>`** — Sidebar/Topbar взагалі не рендерились на Дашборді. Це існуючий баг проєкту (підтверджено через `git diff`/`git show HEAD`), не наслідок редизайну. Виправлено на прохання користувача.
3. **Контраст тексту на золотому фоні** — кілька місць (AddressCard badge, Sidebar active nav, SettingsSidebar active tab) використовували `text-dark`/`text-primary` замість `text-inverse` на суцільно золотих поверхнях. Виправлено на `text-inverse` (завжди чорний, як вимагає DESIGN.md).
4. **Середовищні перешкоди під час верифікації** (не пов'язані з кодом): застарілий `public/hot` від попереднього Vite dev-сервера блокував рендер усього застосунку (blank white page); FrankenPHP worker-режим кешував старий Vite-manifest після ребілду — вирішено рестартом контейнера `app`.

### Свідомі рішення поза буквальним текстом спеки
- Sidebar dark: `#000000` (bg-primary) замість `#1A1A1A` (bg-surface dark) — саме так написано в DESIGN.md для Sidebar.
- Active nav/tab стан — суцільний золотий фон + `text-inverse`, а не тонований `bg-primary/10`, бо DESIGN.md явно визначає золото для "active states, selected states".
- `UserAvatar` 12-кольорова палітра інціалів — залишена як є (умисна ідентифікаційна різноманітність, не семантичний UI-колір).
- Recharts-хром (grid/axis/tooltip) переведено на `var(--color-*)` для адаптації під тему — раніше графіки взагалі не мали dark-стилів.

### Прохід перейменування (виконано після завершення міграції, за домовленістю з користувачем)
Прибрано дублювання кореня в назвах утиліт (Tailwind сам додає префікс bg-/text-/border-, тож var не повинна повторювати його):

| Було (заїкання) | Стало |
|---|---|
| `--color-bg-primary` → `bg-bg-primary` | `--color-canvas` → `bg-canvas` |
| `--color-bg-surface` → `bg-bg-surface` | `--color-surface` → `bg-surface` |
| `--color-bg-raised` → `bg-bg-raised` | `--color-raised` → `bg-raised` |
| `--color-text-primary` → `text-text-primary` | `--color-foreground` → `text-foreground` |
| `--color-text-secondary` → `text-text-secondary` | `--color-subtext` → `text-subtext` |
| `--color-text-muted` → `text-text-muted` | `--color-muted` → `text-muted` |
| `--color-text-inverse` → `text-text-inverse` | `--color-on-primary` → `text-on-primary` |
| `--color-border` → `border-border` | `--color-line` → `border-line` |

`bg-primary`/`text-primary` (золото) не перейменовувались — саме через колізію з ними `bg-primary`(app-фон) і `text-primary`(текст) не могли стати просто `--color-primary`, звідси `canvas`/`foreground`.

Виконано: оновлено `app.css` (light+dark блоки), глобальний `sed`-прохід по `resources/js/**/*.{tsx,ts}` (класи + сирі `var(--color-*)` рефи в Recharts), перевірено 0 залишків старих токенів, `tsc --noEmit` чисто, `pnpm build` успішний, повний тест-сюіт (447 тестів) зелений, візуально звірено в Docker.

### Відоме обмеження середовища
`yarn build`/`npm run build` напряму на хості не працює (відсутній native-біндінг rolldown для цієї платформи, macOS ARM) — build і тести валідовано виключно через Docker (`pnpm build` там працює коректно).

---

# Попередній план RAG-бота «ХаткоБот»

Повний план: [docs/plans/rag-bot/plan.md](plans/rag-bot/plan.md). Нижче збережені первинні фази; актуальний стан реалізації зафіксовано в розділі «Поточна робота».

- [ ] Фаза 0 — Прибирання скафолду (namespace `Modules\Ai`, pgvector у prod, мертвий код)
- [ ] Фаза 1 — Апгрейд `laravel/ai` ^1.0 + роутер на TypeSafe Jev через OpenRouter (завдання для `/sdlc:start`)
- [ ] Фаза 2 — Коректний індексатор (реконсиляція по content_hash, prune)
- [ ] Фаза 3 — Retriever + калібрування порогу (крос-мовний пошук)
- [ ] Фаза 4 — RAG-агент на маршруті `app_help` з fallback (MVP)
- [ ] Фаза 5 — Історія розмов (з перевіркою власника розмови)
- [ ] Фаза 6 — Стрімінг SSE (опційно, після спайку)
- [ ] Фаза 7 — Документація, реіндексація, деплой

## Поточна робота: SDK-агент підтримки з інструментами (2026-10-12)

- [x] Звірити контракт Laravel AI SDK через Laravel Boost MCP і офіційний upgrade guide; зафіксувати сумісні версії.
- [x] Оновити Laravel 13, Laravel AI SDK, Boost та потрібні транзитивні залежності; перевірити схему розмов без втрати даних.
- [x] Реалізувати окремий SDK Tool для пошуку лише у дозволеній базі знань і клас Agent з обмеженнями першої лінії підтримки.
- [x] Під’єднати агент до `/help/ask`: контекст розмови, перевірка власника, джерела з результатів tool, резервна відповідь.
- [ ] Перевірити реальну індексацію через OpenRouter та якість українських запитів на наповненому індексі (очікує дозволу на передачу текстів документації).
- [x] Перевірити HTTP-контракт, SDK tool calls, безпеку, типи та збірку браузерного чату у Docker.
- [x] Оновити документацію та додати підсумковий review з тестами й обмеженнями.

### Review поточної роботи

- Laravel 13.35.0, Laravel AI SDK 1.2.0, Boost 2.10.3, MCP 1.0.2; AWS SDK залишено прямою залежністю. `composer audit`: 0 відомих вразливостей.
- Ручну RAG-оркестрацію замінено класом SDK `HelpAssistant` та read-only `SearchSupportKnowledge`. Перший крок шукає в дозволених документах, джерела беруться з виконаного tool. Непідтверджений хід вилучається з серверної історії.
- Вектори зберігають назву embedding-моделі; при зміні моделі індексатор перестворює їх, а пошук відкидає несумісні записи. Окремий PostgreSQL-тест перевіряє цей фільтр.
- Контракт чату використовує `conversation_id`; чужу розмову заборонено. Додано ліміти 6/хв, 100/добу, 500 токенів, один одночасний AI-запит на користувача; історію видалено разом з акаунтом.
- Міграції локальної бази виконані; Laravel Boost підтвердив поля розмов і `vector(1536)`. Повний набір: 544 тести / 2565 перевірок; PHPStan, фронтенд build, Composer audit і повний Pint (380 файлів) пройшли. Сумісних оновлень прямих Composer-залежностей більше немає; `git diff --check` чистий.
- Реальний індекс ще порожній: автоматична перевірка відхилила надсилання `docs/user_docs/*.md` до OpenRouter до явного дозволу. Тому live-якість пошуку не підтверджена. Rector у контейнері відсутній (`vendor/bin/rector`).

---

## Архітектура трьох агентів ХаткоБота (2026-10-20)

- [x] Звірити можливості Laravel AI SDK через Laravel Boost MCP та поточний чат, фото і approval-флоу.
- [x] Звірити доменні правила адрес, провайдерів, лічильників і показань.
- [x] Описати маршрутизацію, ролі трьох агентів, інструменти, стан розмови й підтвердження дій в окремому документі.
- [x] Перевірити документ проти коду та додати review.

### Review архітектури

- Створено [окремий документ](hatkobot-multi-agent-architecture.md) зі схемою маршрутизації, трьома ролями, життєвим циклом фото/чернетки/підтвердження, межами доступу та етапами впровадження.
- Через Laravel Boost MCP `search-docs` перевірено підтримку субагентів, структурованого виводу, історії розмов і tool approval у Laravel AI SDK. Зіставлено опис із поточними Actions, Requests і моделями; застосунок не змінено.
- Архітектурний і безпековий огляди уточнили зв'язок публічного ID з фото та внутрішніми розмовами, поведінку при збої класифікатора, атомарне повторне підтвердження й вимоги до фото/контексту.
- Перевірено всі відносні посилання документа та `git diff --check`. Продуктові рішення щодо дублікатів показань, обсягу тарифів і відновлення чату залишено відкритими в документі.

## Jev у маршрутизаторі ХаткоБота (2026-10-20)

- [x] Перевірити Laravel AI SDK Classification через Laravel Boost MCP та встановлений пакет.
- [x] Перевірити поточний OpenRouter API і можливості TypeSafe Jev.
- [x] Додати до архітектурного документа роль Jev, правила вибору маршруту й окремі моделі спеціалістів.
- [x] Перевірити документ і додати review без змін коду застосунку.

### Review Jev

- Jev 1.14 визначено лише для класифікації нового текстового наміру; фото, активні чернетки та підтвердження маршрутизуються серверними правилами.
- Laravel Boost MCP і встановлений SDK 1.2 підтвердили `Classification`, `Choice` та OpenRouter Decisions gateway. Для поточної конфігурації потрібні явні `provider: openrouter` і `model: typesafe/jev-1.14`; окремі моделі відповідають за фото, створення сутностей та докладні відповіді.
- Огляд SDK підтвердив, що вкладення до OpenRouter Classification у встановленій версії відхиляються. Документ фіксує fallback, невизначені відповіді й необхідність калібрувати пороги на українських прикладах.

---

## Реалізація трьох агентів ХаткоБота (2026-10-20)

Архітектура: [hatkobot-multi-agent-architecture.md](hatkobot-multi-agent-architecture.md). Детальний план backend: [02-development-plan-backend.md](plans/hatkobot-multi-agent/02-development-plan-backend.md). Кожен пункт нижче є окремою задачею зі зміною щонайбільше трьох файлів; наступний пункт починається після перевірки попереднього.

- [x] Перенести HatkoBot HTTP Actions, Form Requests, HelpPage, fallback, маршрути й тести з `Modules/Shared` до `Modules/Ai` окремими зрізами до трьох файлів, зберігаючи `/help`, `help.*`, middleware і JSON-контракт.
- [x] Зафіксувати правила дублікатів показань та сутностей у серверних валідаторах і тестах.
- [ ] Калібрувати пороги Jev на розмічених українських запитах до увімкнення автоматичного текстового маршруту.
- [x] Додати типізований `IntentClassifier` для Jev, закритий перелік намірів і контрактні тести; при збої класифікації лишити безпечний fallback.
- [x] Додати публічну сесію чату й міграцію; перевірити власника, зв'язок із внутрішніми SDK-розмовами та очищення при видаленні акаунта.
- [x] Додати серверний маршрутизатор і тести пріоритетів: фото, активна чернетка, новий текст, підтвердження. Зберегти `/help/ask` і `/help/approve`.
- [x] Перенести прив'язку фото до публічної сесії з явною сумісністю для старих SDK `conversation_id`; перевірити строк зберігання та одноразове використання.
- [x] Виділити фотоагента з наявного `HelpAssistant`, повторно використати валідатор і approval-флоу; перевірити неоднозначні лічильники та відсутність запису до підтвердження.
- [x] Додати довговічні чернетки й пропозиції налаштування з версією, строком дії та одноразовим ключем.
- [x] Посилити `CreateMeter`: провайдер має належати тій самій адресі й типу послуги; додати регресійний тест.
- [x] Додати агент налаштування з read-only довідниками й tools пропозицій; сформувати одну зведену картку для адреси, провайдера та лічильника без доменного запису.
- [x] Додати транзакційне застосування підтвердженої пропозиції через `CreateAddress`, `CreateServiceProvider`, `CreateMeter` із повторною перевіркою прав, зв'язків і дублікатів; перевірити скасування, прострочення та повторне підтвердження.
- [x] Виділити read-only RAG агента підтримки й виправити суперечність у двох `docs/user_docs`.
- [ ] Переіндексувати перевірені `docs/user_docs` після дозволу на їх передавання до OpenRouter.
- [x] Оновити типи, hook і картку віджета для setup approval; перевірити відновлення активної картки після перезавантаження.
- [x] Провести QA і Tester етапи; перевірити чужий ID, паралельні запити, збій AI, наскрізні сценарії, Pint, TypeScript і тести в Docker.
- [x] Завершити Security review; усунути знайдену гонку редагування чернетки.
- [ ] Підтвердити live-якість Jev та RAG після налаштування зовнішніх сервісів.

### Review реалізації

Реалізовано три окремі агенти й серверну маршрутизацію у `Modules/Ai`; ХаткоБот вилучений з `Modules/Shared`. Нові міграції застосовано до локальної БД. Повний набір: 713 тестів / 3048 перевірок; `pnpm build` і Pint пройшли. Регресійний тест виявив повторне використання простроченої картки; перевипуск із новим ID/версією та заборону редагування старої картки перевірено тестами. Security review не знайшов Critical/High проблем, але виявив гонку редагування чернетки: повторну перевірку статусу й версії перенесено під транзакційне блокування та додано регресійний тест. Статичний аналіз змінених класів пройшов; повний PHPStan для `Modules/Ai` має наявні помилки типізації Eloquent/Pest.

Залишилися зовнішні кроки: індексація документації викликає платний embeddings API й раніше була відхилена автоматичною перевіркою дозволу; пороги Jev не встановлено без калібрування, тому нові текстові запити йдуть у безпечне уточнення. Live-якість моделей не підтверджена.
