# Frontend Implementation Plan: Виправлення відображення завантажених зображень

## Scope
Фронтенд-аспект задачі `fix-uploaded-images-not-displaying`. Бекенд (symlink-фікс) уже реалізовано окремим агентом; Inertia-контракт (`auth.user.avatar`, `MeterResource.photo`, `MeterReadingResource.photos[]`) підтверджено достатнім і незмінним. Ця робота торкається лише `resources/js/**`.

## Верифікація поточного стану (проти BA Story 1–7)

Пряме читання коду показало, що обсяг роботи вужчий, ніж передбачав BA-документ — частина Stories вже реалізована в поточній гілці (коміти `photo-background-processing`):

| Story | Стан | Доказ у коді |
|---|---|---|
| 1. Аватар відображається + спінер під час обробки + авто-refresh | **Вже реалізовано повністю** | `Layouts/AuthenticatedLayout.tsx:54-68` — `usePoll(4000, { only: ['auth'] })`, старт/стоп за `authUser?.avatar?.is_processing`; `UserAvatar.tsx` — `onError`→ініціали, `isProcessing` оверлей зі `Spinner`. `Settings/Index.tsx` обгортає `ProfileTab` в `AuthenticatedLayout`, тож поллінг вже покриває сторінку налаштувань. |
| 2. Фото лічильника в картці + спінер + поллінг списку | **Вже реалізовано повністю** | `MeterCard.tsx` — `resolveMediaSrc`, `Spinner`-плейсхолдер, `is_processing`-бейдж; `Meters/Index.tsx:33-43` — `usePoll(4000, { only: ['meters'] })`. |
| 3. Фото показань відображаються користувачу | **НЕ реалізовано — реальний функціональний пробіл** | `Readings/Index.tsx` рендерить `readingList` (через `useDenormalizeAuto<ReadingWithMeterName>`), але ніде не звертається до `reading.photos`. `types/entities.ts` вже має `ReadingPhoto[]` в `Reading.photos` — тип готовий, рендеру немає. (`ReadingCard.tsx` — це компонент **чернетки** на `Readings/Create.tsx` з локальним blob-прев'ю вибраного файла, **не** місце показу вже збережених фото; плутати ці два компоненти не можна.) |
| 4/5. URL віддає байти / черга конверсій | Поза скоупом фронтенду (інфраструктура/бекенд, вже покрито backend-аспектом) | — |
| 6. UX-плейсхолдер під час обробки (замість зламаного `<img>`) | Реалізовано для аватара й фото лічильника; **потрібен для фото показань** (нова функціональність) | `UserAvatar`, `MeterCard` вже коректні; новий рендер для Story 3 має наслідувати той самий патерн. |
| 7. HEIC/HEIF fallback | **Частково вразливо — потребує фіксу** | `lib/media.ts::resolveMediaSrc` falls back на `original_url`, коли `is_processing=false`, без перевірки формату. Якщо оригінал HEIC і жодна конверсія ще не встигла з'явитись (edge-case гонки: `is_processing` вже `false`, але frontend отримав застарілий кеш `optimized_url: null`) — браузер не відрендерить. Контракт **не містить** `mime_type` (підтверджено `types/media.ts` і backend-звітом) — розпізнавання можливе лише евристично, за розширенням у самому URL. |

**Висновок:** основна робота — Story 3 (рендер фото показань) + цілеспрямований UX-фікс для Story 7 (HEIC) у спільній точці (`resolveMediaSrc`), застосований транзитивно до всіх трьох місць. Stories 1 і 2 — лише звірка (regression-перевірка, без змін коду).

## Files to create

### `resources/js/Components/ui/MediaThumbnail.tsx` (новий, named export)
Спільний прямокутний thumbnail-компонент для **фото лічильника** (retrofit) і **фото показань** (нова функціональність). Інкапсулює один раз: preferredConversion → `resolveMediaSrc` fallback, `Spinner`-плейсхолдер під час завантаження/обробки, `onError`→іконка-заглушка (без «зламаного» `<img>`), опційний `is_processing`-індикатор (кутовий бейдж), опційне обгортання в `<a target="_blank" rel="noopener noreferrer">` для перегляду повного зображення.

**Чому окремий компонент, а не розширення `UserAvatar`:** аватар — кругла форма з fallback на ініціали (текстова, іменна заглушка); `MediaThumbnail` — прямокутна форма з fallback на нейтральну іконку (немає «імені» для генерації заглушки). Це різні візуальні контракти, об'єднання в один компонент з умовними гілками (`shape: 'circle' | 'square'`) було б передчасною абстракцією (YAGNI) заради двох викликів. Дублювання, яке справді існує — між **MeterCard** і **новим рендером фото показань** (обидва: прямокутний thumbnail + spinner + is_processing-бейдж + onError-fallback) — саме це усуває `MediaThumbnail`.

```tsx
export interface MediaThumbnailProps {
  media: MediaConversionUrls
  alt: string
  size?: 'sm' | 'md' // sm=64px (картка лічильника), md=96px (грід фото показань)
  href?: string // якщо задано — обгортає в <a target="_blank"> для перегляду оригіналу
  className?: string
}
export function MediaThumbnail({ media, alt, size = 'md', href, className }: MediaThumbnailProps) { ... }
```

Додати `export * from './MediaThumbnail'` у `resources/js/Components/ui/index.ts`.

## Files to modify

### `resources/js/lib/media.ts`
Додати мінімальну евристику HEIC/HEIF-захисту у `resolveMediaSrc`: якщо кандидат на fallback — `original_url`, і рядок URL закінчується на `.heic`/`.heif` (case-insensitive, без query-string), повертати `null` замість URL (тобто UI лишається у стані «обробка», а не показує непридатний для рендеру браузером файл). Це єдине місце фіксу — застосовується транзитивно до аватара, фото лічильника і фото показань без дублювання логіки.

```ts
const HEIC_EXTENSION_PATTERN = /\.hei[cf]$/i

export function resolveMediaSrc(
  media: MediaConversionUrls | null | undefined,
  preferredConversion: 'optimized_url' | 'thumbnail_url' = 'optimized_url',
): string | null {
  if (!media) return null
  if (media[preferredConversion]) return media[preferredConversion]
  if (media.is_processing) return null
  if (HEIC_EXTENSION_PATTERN.test(new URL(media.original_url).pathname)) return null
  return media.original_url
}
```

**Design decision:** евристика за розширенням URL, не за `Content-Type`/mime — контракт не передає mime, а зміна контракту поза скоупом фронтенд-агента (задокументовано backend-звітом як свідомо не зроблено). `new URL(...).pathname` захищає від хибних спрацювань на query-параметрах (напр. підписані S3-URL з `?signature=...heic`).

### `resources/js/Pages/Readings/Index.tsx`
Головна зміна для Story 3:
1. Під `CardContent` кожної картки показання (після `dl` з показаннями / нотаток) додати блок рендеру `reading.photos`: якщо `reading.photos.length > 0`, рендерити горизонтальний `flex flex-wrap gap-2` грід `MediaThumbnail` (`size="sm"`, `href` = `resolveMediaSrc(photo, 'optimized_url') ?? photo.original_url` для повноекранного перегляду в новій вкладці, `alt="Фото показання від {formatReadingDate}"`), ключ — `photo.id`.
2. Додати авто-поллінг за аналогією з `Meters/Index.tsx`: `const hasProcessingPhoto = readingList.some((r) => r.photos.some((p) => p.is_processing))`, `usePoll(4000, { only: ['readings'] })`, старт/стоп у `useEffect` за `hasProcessingPhoto` (ідентичний патерн, включно з `eslint-disable-next-line react-hooks/exhaustive-deps` на poll-обгортці, як у `Meters/Index.tsx`).
3. Імпорти: додати `usePoll` до `import { Head, router, usePoll } from '@inertiajs/react'`, `useEffect` до React-імпортів, `MediaThumbnail` та `resolveMediaSrc`.

Секцію показань не потрібно чіпати на `Readings/Create.tsx` — там фото ще не збережені (лише локальний вибір файла через `PhotoDropzone`/`ReadingCard`), контракту `photos[]` там немає.

### `resources/js/Pages/Meters/Components/MeterCard.tsx` (retrofit — рекомендовано, не обов'язково)
**Рішення: так, ретрофітити.** Поточний блок (рядки 95–114: ручний `resolveMediaSrc` + `Spinner`-плейсхолдер + позиційований `is_processing`-бейдж) — це той самий візуальний контракт, який щойно виноситься в `MediaThumbnail` для фото показань. Залишити дублюючу копію логіки в `MeterCard` після появи спільного компонента порушило б DRY, який сам BA-бриф явно просить уникнути («щоб не дублювати спінер/fallback логіку тричі»). Заміна:

```tsx
{meter.photo ? (
  <MediaThumbnail
    media={meter.photo}
    alt={`Фото ${meter.name}`}
    size="sm"
    className="flex-shrink-0"
  />
) : null}
```

Ризик низький: чисто візуальний рефакторинг без зміни поведінки (`resolveMediaSrc`, `Spinner`, `is_processing`-бейдж — та сама логіка, лише інкапсульована). `UserAvatar` — **не чіпати**, вона зберігає власний, відмінний контракт (кругла форма, ініціали).

### `resources/js/Pages/Settings/Components/ProfileTab.tsx`
**Без змін коду.** Поллінг аватара вже покритий на рівні `AuthenticatedLayout` (обгортає `Settings/Index.tsx`), який передає `avatarIsProcessing` у `AuthenticatedTopbar`/`AuthenticatedSidebar`. `ProfileTab` сам показує стан обробки через `PhotoDropzone`'s `isProcessing` пропс (рядок 82: `!selectedFileBlobUrl && (user?.avatar?.is_processing ?? false)`), який стане `false` автоматично, коли поллінг у layout оновить `user` (через спільний `useAuthUser()`/`usePage`). Verification-крок: підтвердити це вручну/через Pest browser test у наступній фазі — не додаткова імплементація.

## Implementation order
1. `lib/media.ts` — HEIC-захист у `resolveMediaSrc` (найменша, ізольована зміна, не залежить від інших кроків).
2. `Components/ui/MediaThumbnail.tsx` + export в `index.ts` (новий спільний компонент).
3. `Readings/Index.tsx` — рендер `photos[]` + поллінг (основна функціональна зміна, Story 3).
4. `Meters/Components/MeterCard.tsx` — retrofit на `MediaThumbnail` (низькоризиковий рефакторинг, після появи компонента).
5. Verification: `npx tsc --noEmit`, `pnpm lint` (якщо є скрипт), `pnpm build`.

## Key design decisions (ui-ux-max)
1. **Спінер, не skeleton, для стану обробки.** Проєкт вже послідовно використовує компонент `Spinner` (обертовий бордер) для processing-стану в трьох місцях (`UserAvatar`, `MeterCard`, `PhotoDropzone`) — жодного skeleton-патерна (пульсуючий прямокутник) в кодовій базі немає. `MediaThumbnail` наслідує вже усталений візуальний словник (`Spinner size="xs"` в кутовому бейджі на напівпрозорому чорному фоні `bg-black/60`), а не вводить нову мову компонентів — консистентність важливіша за «ідеальний» skeleton-паттерн, якого тут ніде немає.
2. **Fallback на нейтральну іконку-заглушку (не порожній простір, не «зламаний» `<img>`).** Коли `onError` спрацьовує (мережева помилка, 404, неможливий для рендеру формат), `MediaThumbnail` показує прямокутник `bg-surface border border-line` з іконкою `ImageOff` (lucide-react, вже використовується проєктом як бібліотека іконок) замість порожнього блоку — дає користувачу зрозумілий сигнал «фото недоступне», а не мовчазну відсутність контенту.
3. **`size="sm"` (64px) для карток лічильника, `size="md"` (96px) для гріда фото показань.** Показання можуть мати кілька фото (`photos[]` — масив), тому потрібен трохи більший розмір для розрізнення деталей у горизонтальному гріді; лічильник має рівно одне фото в контексті рядка картки — 64px консистентний з існуючим `h-16 w-16` в `MeterCard`.
4. **`href` → перегляд оригіналу в новій вкладці замість модального lightbox.** Побудова нового модального компонента перегляду зображень — окрема, немала UI-фіча (керування фокусом, ARIA `dialog`, свайп-навігація між кількома фото показання), не описана явно в acceptance criteria BA (Story 3 вимагає лише «фото відображаються»). `<a target="_blank" rel="noopener noreferrer">` — мінімальне, доступне (нативна навігація браузера) рішення, що закриває вимогу без спекулятивної абстракції; якщо стейкхолдер згодом попросить lightbox — окрема задача.
5. **Accessibility: `alt` завжди змістовний, ніколи `alt=""` для контентних фото.** На відміну від `UserAvatar` (де `alt=""` навмисний, бо `role="img" aria-label={name}` на контейнері вже озвучує аватар для screen reader — уникнення подвійного озвучування), `MediaThumbnail` рендерить контентне фото без такого батьківського `aria-label`, тому потребує власного описового `alt` (`"Фото {назва лічильника}"`, `"Фото показання від {дата}"`) — відповідає WCAG 1.1.1.
6. **HEIC-подавлення через розширення URL, не через приховування помилки в момент рендеру.** Активний вибір — краще показати «в обробці» трохи довше (до появи `optimized_url`), ніж один раз відрендерити `<img>`, який гарантовано не покажеться в Safari/Chrome для HEIC, що виглядало б як регресія в стан «зламаного» зображення, якого весь цей фікс і мав позбутися (BA AC Story 1: «а не зламане зображення»).

## Skills to invoke
- `ui-ux-max` — застосовано вище (спінер-консистентність, fallback-іконка, розміри, accessibility, рішення проти lightbox).
- `react-plugin:react-conventions` — named exports для компонентів (`MediaThumbnail`), default export для сторінок (`Readings/Index.tsx` не змінює патерн експорту); файлова організація в `Components/ui/`.
- `react-plugin:react-state-management` — локальний `useEffect`+`usePoll` стан для авто-refresh (без нового глобального стору — Inertia page props вже є джерелом істини, підтверджено існуючим патерном `Meters/Index.tsx`/`AuthenticatedLayout.tsx`).
- `react-plugin:react-forms` — не застосовується напряму (ця зміна не додає нових форм), звірено, що жодна існуюча форма (`useForm` в `ProfileTab`, `Readings/Create`) не чіпається.
- `js-foundation:typescript-patterns` — типізація `MediaThumbnailProps` через `interface` (не `type`, за `code-style.md`), без `any`; повторне використання наявних `MediaConversionUrls`/`ReadingPhoto` з `types/media.ts`/`types/entities.ts`.
- `js-foundation:npm-patterns` — жодних нових залежностей не потрібно (`lucide-react` вже в `package.json`, використовується у 10+ місцях).

## Risks and edge cases
1. **Гонка заміни аватара (singleFile) під час обробки старого файла.** Не зачіпається цим фронтенд-аспектом — вже коректно оброблено (`selectedFileBlobUrl` пріоритетний над `savedAvatarSrc` до завершення `post`, коментар у `ProfileTab.tsx:29-32` явно описує це рішення). Верифікація для наступної фази (qa/tester), не код-зміна.
2. **Поллінг без cleanup на unmount.** `usePoll` з `@inertiajs/react` керує власним таймером і зупиняється при демонтуванні компонента (бібліотечна поведінка); явний `stop()` у `useEffect` cleanup не додається — узгоджено з наявним патерном `Meters/Index.tsx`/`AuthenticatedLayout.tsx`, де так само немає ручного cleanup. Якщо в майбутньому виявиться memory leak — це системний, а не локальний для цієї зміни риск.
3. **`reading.photos` може бути порожнім масивом (не `null`).** Контракт (`MeterReadingResource`) гарантує `[]`, а не `null`/`undefined` для показань без фото — умова рендеру `reading.photos.length > 0` безпечна без опціонального ланцюжка.
4. **HEIC-евристика — не 100% надійна.** URL-based `.heic`/`.heif`-перевірка спрацює лише якщо шлях дійсно містить розширення файла (стандартна поведінка Laravel Media Library — так і є, `getUrl()` зберігає оригінальне розширення). Якщо колись `MEDIA_DISK` перейде на S3 з підписаними URL без розширення в шляху (query-based), евристика перестане спрацьовувати — задокументовано як follow-up, не блокер зараз.
5. **`MeterCard` retrofit — чисто візуальний ризик regresії.** Мінімізовано ідентичною логікою (`resolveMediaSrc`, той самий `Spinner`, той самий `is_processing`-бейдж), рекомендовано verification через існуючі Pest browser-тести для `Meters/Index` (якщо є) в наступній QA-фазі.
6. **Немає `mime_type` в контракті** — якщо стейкхолдер згодом захоче надійніший HEIC-детект (не за розширенням URL), знадобиться розширення бекенд-контракту (`ResolvesMediaConversionUrls`) — поза скоупом цього фіксу, задокументовано в backend-звіті як відкрите питання.

## Known follow-ups for next phases
- **qa-engineer**: Pest browser test для `Readings/Index` — фото показань рендеряться, `is_processing` показує спінер, поллінг припиняється після завершення обробки (`assertNoJavaScriptErrors()`).
- **qa-engineer**: regression-перевірка Story 1/2 (аватар і фото лічильника) — підтвердити, що існуюча поведінка не зламана retrofit'ом `MeterCard`.
- **stakeholder decision needed** (перенесено з backend-плану, актуально й тут): чи потрібен повноцінний lightbox для перегляду фото показань замість `target="_blank"` — окрема UX-фіча, не частина цього фіксу.
