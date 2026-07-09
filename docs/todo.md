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
- [ ] `docker compose exec app php artisan test tests/Browser/` — усі зелені
- [ ] Скріншоти (chrome-devtools MCP) кожної групи сторінок у light і dark
- [ ] Grep підтверджує 0 старих утиліт/токенів
- [ ] Ручна звірка з `docs/DESIGN.md`: колір золота, OLED-фон, шрифт Inter, контраст

## Review (заповнити після завершення)
