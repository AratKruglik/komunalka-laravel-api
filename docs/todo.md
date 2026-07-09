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
