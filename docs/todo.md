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
- [ ] Auth: Login/Register/ForgotPassword/ResetPassword
- [ ] Dashboard + партіали (WelcomeHeader, ConsumptionChart, ExpenseDistribution, RecentReadingsTable, QuickActions) — багатоколірна палітра графіків
- [ ] Addresses (Index/Create/Edit + AddressCard/AddressForm)
- [ ] Meters (Index/Create/Edit + MeterCard/MeterForm)
- [ ] Providers (Index/Create/Edit + ProviderForm)
- [ ] Readings (Index/Create + ReadingCard/ReadingSummaryTable)
- [ ] Settings (усі таби + SettingsSidebar/ThemeCard/ConnectedAccountCard)
- [ ] Фінальний grep старих утиліт → 0 посилань → видалити alias з app.css
- [ ] Чекпойнт: повний `tsc`, `yarn build`, візуальний прохід усіх сторінок

## Верифікація (перед завершенням)
- [ ] `docker compose exec app php artisan test tests/Browser/` — усі зелені
- [ ] Скріншоти (chrome-devtools MCP) кожної групи сторінок у light і dark
- [ ] Grep підтверджує 0 старих утиліт/токенів
- [ ] Ручна звірка з `docs/DESIGN.md`: колір золота, OLED-фон, шрифт Inter, контраст

## Review (заповнити після завершення)
