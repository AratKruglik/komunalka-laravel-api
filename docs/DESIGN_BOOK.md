# DesignBook — Komunalka

Complete design system reference for the project. Use as a single source of truth when working on the UI.

---

## Table of Contents

1. [Tech Stack](#tech-stack)
2. [Color Palette](#color-palette)
3. [Typography](#typography)
4. [Spacing & Sizing](#spacing--sizing)
5. [Shadows](#shadows)
6. [Dark Mode](#dark-mode)
7. [UI Components](#ui-components)
   - [Button](#button)
   - [Badge](#badge)
   - [Alert](#alert)
   - [Spinner](#spinner)
   - [Card](#card)
   - [ConfirmDialog](#confirmdialog)
   - [DropdownMenu](#dropdownmenu)
   - [UserAvatar](#useravatar)
   - [Logo](#logo)
8. [Forms](#forms)
   - [Input](#input)
   - [Select](#select)
   - [Textarea](#textarea)
   - [Checkbox](#checkbox)
   - [Label](#label)
   - [RadioCard](#radiocard)
   - [PhotoDropzone](#photodropzone)
9. [Navigation & Layouts](#navigation--layouts)
   - [AuthenticatedLayout](#authenticatedlayout)
   - [AuthenticatedSidebar](#authenticatedsidebar)
   - [AuthenticatedTopbar](#authenticatedtopbar)
10. [Shared TV Styles](#shared-tv-styles)
11. [Page Patterns](#page-patterns)
12. [Icons](#icons)

---

## Tech Stack

| Technology | Version | Purpose |
|---|---|---|
| Tailwind CSS | v4 | Utility-first CSS framework |
| Tailwind Variants (`tv()`) | — | Component variant system |
| React | 19 | UI components (`.tsx`) |
| Inertia.js | v2 | SPA with server-driven routing |
| Lucide React | — | Icon library |

All components are functional (no class components). Exports are named (not default).

---

## Color Palette

Colors are declared as CSS Custom Properties in `resources/css/app.css` via `@theme {}`.

### Primary Colors

| Variable | HEX | Description |
|---|---|---|
| `--color-primary` | `#FFD700` | Brand yellow (accent) |
| `--color-primary-dark` | `#FFC700` | Darker primary — hover state |
| `--color-primary-light` | `#FFE44D` | Lighter primary |
| `--color-primary-bg` | `#FFF0A0` | Background for active states |
| `--color-primary-active` | `#FFB700` | Active state |
| `--color-primary-stroke` | `#FFA500` | Stroke for primary elements |

### Secondary Colors

| Variable | HEX | Description |
|---|---|---|
| `--color-secondary` | `#8b5cf6` | Purple |
| `--color-secondary-dark` | `#7c3aed` | Purple hover |
| `--color-secondary-light` | `#a78bfa` | Light purple |
| `--color-accent` | `#10b981` | Green accent |
| `--color-accent-dark` | `#059669` | Darker green |
| `--color-accent-light` | `#34d399` | Lighter green |

### Semantic Colors

| Variable | HEX | Usage |
|---|---|---|
| `--color-success` | `#10b981` | Success, positive actions |
| `--color-warning` | `#f59e0b` | Warnings |
| `--color-error` | `#ef4444` | Errors |
| `--color-error-dark` | `#D92D20` | Dark error (logout, etc.) |
| `--color-info` | `#3b82f6` | Informational elements |

### Neutral Colors

| Variable | HEX |
|---|---|
| `--color-neutral-50` | `#f9fafb` |
| `--color-neutral-100` | `#f3f4f6` |
| `--color-neutral-200` | `#e5e7eb` |
| `--color-neutral-300` | `#d1d5db` |
| `--color-neutral-400` | `#9ca3af` |
| `--color-neutral-500` | `#6b7280` |
| `--color-neutral-600` | `#4b5563` |
| `--color-neutral-700` | `#374151` |
| `--color-neutral-800` | `#1f2937` |
| `--color-neutral-900` | `#111827` |
| `--color-neutral-950` | `#030712` |

### Text Colors

| Variable | HEX | Usage |
|---|---|---|
| `--color-text-primary` | `#111827` | Primary text |
| `--color-text-secondary` | `#6b7280` | Secondary text |
| `--color-text-tertiary` | `#9ca3af` | Tertiary text |
| `--color-text-dark` | `#333333` | Dark text |
| `--color-text-placeholder` | `#adaebc` | Input placeholder |

### Background Colors

| Variable | HEX | Usage |
|---|---|---|
| `--color-bg-primary` | `#ffffff` | Main background |
| `--color-bg-secondary` | `#f9fafb` | Secondary background (neutral-50) |
| `--color-bg-tertiary` | `#f3f4f6` | Tertiary background (neutral-100) |

### Border Colors

| Variable | HEX |
|---|---|
| `--color-border-primary` | `#e5e7eb` |
| `--color-border-secondary` | `#d1d5db` |

### Links & Badges

| Variable | HEX |
|---|---|
| `--color-link` | `#DAA520` |
| `--color-link-hover` | `#B8860B` |
| `--color-badge-danger` | `#FF4D4F` |

---

## Typography

### Fonts

| Variable | Value |
|---|---|
| `--font-sans` | `'Montserrat', ui-sans-serif, system-ui, sans-serif` |
| `--font-mono` | `ui-monospace, SFMono-Regular, "SF Mono", Menlo, Consolas, ...` |

The application's primary font is **Montserrat**.

### Font Sizes

| Variable | rem | px |
|---|---|---|
| `--font-size-xs` | `0.75rem` | 12px |
| `--font-size-sm` | `0.875rem` | 14px |
| `--font-size-base` | `1rem` | 16px |
| `--font-size-lg` | `1.125rem` | 18px |
| `--font-size-xl` | `1.25rem` | 20px |
| `--font-size-2xl` | `1.5rem` | 24px |
| `--font-size-3xl` | `1.875rem` | 30px |
| `--font-size-4xl` | `2.25rem` | 36px |

### Common Text Styles

| Context | Classes |
|---|---|
| Page title (topbar) | `text-lg font-semibold` / `text-xl` / `text-[22px]` |
| Section heading (PageSectionHeader) | `text-2xl font-semibold text-gray-900` |
| Card title (CardTitle) | `text-lg font-semibold tracking-tight text-[var(--color-text-dark)]` |
| Card description | `text-sm text-[var(--color-text-secondary)]` |
| Body text | `text-base text-gray-900` / `text-gray-700` |
| Supporting text | `text-sm text-gray-600` |
| Caption / fine print | `text-xs text-gray-500` |
| Form label | `text-sm font-medium text-neutral-800` |
| Sidebar section labels | `text-[10px] font-semibold uppercase tracking-wider text-gray-400` |

---

## Spacing & Sizing

### Spacing Tokens

| Variable | rem |
|---|---|
| `--spacing-xs` | `0.25rem` (4px) |
| `--spacing-sm` | `0.5rem` (8px) |
| `--spacing-md` | `1rem` (16px) |
| `--spacing-lg` | `1.5rem` (24px) |
| `--spacing-xl` | `2rem` (32px) |
| `--spacing-2xl` | `3rem` (48px) |
| `--spacing-3xl` | `4rem` (64px) |

### Border Radius

| Variable | rem | Usage |
|---|---|---|
| `--radius-sm` | `0.25rem` | Small elements |
| `--radius-md` | `0.375rem` | Form inputs |
| `--radius-lg` | `0.5rem` | Cards, buttons |
| `--radius-xl` | `0.75rem` | Modals, dropdowns |
| `--radius-2xl` | `1rem` | PhotoDropzone |
| `--radius-full` | `9999px` | Badges, avatars |

### Transitions

| Variable | Value |
|---|---|
| `--transition-fast` | `150ms` |
| `--transition-base` | `200ms` |
| `--transition-slow` | `300ms` |

---

## Shadows

| Variable | Value |
|---|---|
| `--shadow-sm` | `0 1px 2px 0 rgb(0 0 0 / 0.05)` |
| `--shadow-md` | `0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)` |
| `--shadow-lg` | `0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)` |
| `--shadow-xl` | `0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1)` |

---

## Dark Mode

Dark mode is toggled via the `data-theme="dark"` attribute on `:root`.

CSS Custom Properties that change:

| Variable | Light | Dark |
|---|---|---|
| `--color-bg-primary` | `#ffffff` | `#111827` |
| `--color-bg-secondary` | `#f9fafb` | `#1f2937` |
| `--color-bg-tertiary` | `#f3f4f6` | `#374151` |
| `--color-text-primary` | `#111827` | `#f9fafb` |
| `--color-text-secondary` | `#6b7280` | `#d1d5db` |
| `--color-text-tertiary` | `#9ca3af` | `#9ca3af` |
| `--color-border-primary` | `#e5e7eb` | `#374151` |
| `--color-border-secondary` | `#d1d5db` | `#4b5563` |

Tailwind dark variant `dark:` is activated via the `[data-theme=dark]` selector.

Common dark mode equivalents:

| Light | Dark |
|---|---|
| `bg-white` | `dark:bg-slate-900` |
| `bg-gray-50` / `bg-neutral-50` | `dark:bg-slate-950` |
| `border-gray-200` | `dark:border-slate-800` |
| `text-gray-900` | `dark:text-slate-50` |
| `text-gray-600` | `dark:text-slate-300` |
| `hover:bg-gray-100` | `dark:hover:bg-slate-800` |
| Active nav item | `dark:bg-amber-300 dark:text-slate-900` |
| Focus ring / border | `dark:focus:border-amber-300 dark:focus:ring-amber-300` |

---

## UI Components

Location: `resources/js/Components/ui/`

### Button

File: `Components/ui/Button.tsx`

Built with `tv()` across three variant axes: `variant`, `tone`, `size`.

#### Props

| Prop | Type | Default | Description |
|---|---|---|---|
| `variant` | `'solid' \| 'outline' \| 'ghost' \| 'link'` | `'solid'` | Visual style |
| `tone` | `'primary' \| 'neutral' \| 'secondary' \| 'success' \| 'warning' \| 'danger'` | `'primary'` | Color tone |
| `size` | `'sm' \| 'md' \| 'lg' \| 'xl' \| 'icon'` | `'md'` | Size |
| `fullWidth` | `boolean` | — | Stretch to container width |
| `loading` | `boolean` | `false` | Loading state (shows Spinner) |
| `loadingText` | `string` | — | Text shown while loading |

#### Sizes

| Size | Height | Padding | Text |
|---|---|---|---|
| `sm` | `h-9` (36px) | `px-3` | `text-sm` |
| `md` | `h-10` (40px) | `px-4` | `text-sm` |
| `lg` | `h-11` (44px) | `px-5` | `text-base` |
| `xl` | `h-12` (48px) | `px-6` | `text-base` |
| `icon` | `h-10 w-10` | `p-0` | — |

#### variant × tone Combinations

| Variant | Tone | Result |
|---|---|---|
| `solid` | `primary` | Yellow bg, dark text |
| `solid` | `neutral` | `neutral-800` bg, white text |
| `solid` | `secondary` | Purple bg, white text |
| `solid` | `success` | Green bg, white text |
| `solid` | `warning` | Amber bg, dark text |
| `solid` | `danger` | Red bg, white text |
| `outline` | `primary` | Gray border, dark text |
| `outline` | `neutral` | Gray border, gray text |
| `outline` | `danger` | Red border, red text |
| `ghost` | `neutral` | Transparent, gray text, gray hover |
| `ghost` | `danger` | Transparent, red text |
| `link` | any | No bg, no padding, underline on hover |

#### Disabled State

`disabled:bg-gray-300 dark:disabled:bg-slate-700 disabled:opacity-60`

---

### Badge

File: `Components/ui/Badge.tsx`

Inline pill element for status labels.

#### Props

| Prop | Type | Default |
|---|---|---|
| `variant` | `'primary' \| 'secondary' \| 'neutral' \| 'success' \| 'warning' \| 'danger'` | `'primary'` |

#### Styles by Variant

| Variant | Background | Text | Border |
|---|---|---|---|
| `primary` | `bg-primary/20` | `text-text-dark` | `border-primary/50` |
| `secondary` | `bg-secondary-light/20` | `text-secondary-dark` | `border-secondary-light/50` |
| `neutral` | `bg-neutral-100` | `text-neutral-700` | `border-neutral-200` |
| `success` | `bg-success/15` | `text-success` | `border-success/40` |
| `warning` | `bg-warning/15` | `text-warning` | `border-warning/40` |
| `danger` | `bg-error/15` | `text-error` | `border-error/40` |

Base styles: `rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide`

---

### Alert

File: `Components/ui/Alert.tsx`

System notification component. Composed of `Alert`, `AlertTitle`, `AlertDescription`.

#### Props

| Prop | Type | Default |
|---|---|---|
| `variant` | `'info' \| 'success' \| 'warning' \| 'danger'` | `'info'` |
| `withIcon` | `boolean` | `true` |
| `icon` | `ReactNode` | — (auto by variant) |

#### Default Icons

| Variant | Icon |
|---|---|
| `info` | `<Info />` |
| `success` | `<CheckCircle2 />` |
| `warning` | `<AlertTriangle />` |
| `danger` | `<AlertCircle />` |

`background`, `border`, and `accent` colors are computed via `color-mix()` from the corresponding semantic CSS variables.

#### Sub-components

- `AlertTitle` — heading, color matches the variant accent (`font-semibold text-sm`)
- `AlertDescription` — body text (`text-sm text-[var(--color-text-secondary)]`)

#### Usage in Layout

`AuthenticatedLayout` automatically renders flash messages:
```tsx
{flash.success && <Alert variant="success"><AlertDescription>{flash.success}</AlertDescription></Alert>}
{flash.error && <Alert variant="danger"><AlertDescription>{flash.error}</AlertDescription></Alert>}
```

---

### Spinner

File: `Components/ui/Spinner.tsx`

#### Props

| Prop | Type | Default |
|---|---|---|
| `size` | `'xs' \| 'sm' \| 'md' \| 'lg'` | `'md'` |
| `tone` | `'primary' \| 'warning' \| 'danger' \| 'success' \| 'info'` | `'primary'` |

#### Sizes

| Size | Dimensions |
|---|---|
| `xs` | `h-3 w-3` |
| `sm` | `h-4 w-4` |
| `md` | `h-5 w-5` |
| `lg` | `h-6 w-6` |

---

### Card

File: `Components/ui/Card.tsx`

Bordered container with shadow. Composed of sub-components.

#### Sub-components

| Component | Styles |
|---|---|
| `Card` | `rounded-lg border border-border-primary bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900` |
| `CardHeader` | `flex flex-col gap-1.5 border-b px-6 py-5` |
| `CardTitle` | `text-lg font-semibold tracking-tight text-text-dark` |
| `CardDescription` | `text-sm text-text-secondary` |
| `CardContent` | `px-6 py-6` |
| `CardFooter` | `flex items-center justify-end gap-3 border-t px-6 py-4` |

#### TV Variant (card.ts)

An alternative `tv()` style with extended variants is defined in `Components/ui/styles/card.ts`:

```ts
card = tv({
  base: 'rounded-xl border bg-white shadow-lg dark:bg-slate-900',
  variants: {
    border: { default: 'border-neutral-200', subtle: 'border-neutral-200/80', none: 'border-transparent' },
    padding: { none: '', sm: 'p-3.5 sm:p-4', md: 'p-3.5 sm:p-5 lg:p-6', lg: 'p-4 sm:p-5 lg:p-6', welcome: 'px-3.5 py-4 sm:px-5 sm:py-5 lg:px-6 lg:py-6' },
  },
  defaultVariants: { border: 'default', padding: 'md' },
})
```

---

### ConfirmDialog

File: `Components/ui/ConfirmDialog.tsx`

Modal dialog for confirming destructive actions.

#### Props

| Prop | Type | Default |
|---|---|---|
| `isOpen` | `boolean` | — |
| `onClose` | `() => void` | — |
| `onConfirm` | `() => void` | — |
| `title` | `string` | — |
| `description` | `ReactNode` | — |
| `confirmLabel` | `string` | `'Confirm'` |
| `cancelLabel` | `string` | `'Cancel'` |
| `variant` | `'danger' \| 'warning' \| 'info'` | `'danger'` |
| `isLoading` | `boolean` | `false` |
| `icon` | `ReactNode` | `<AlertTriangle />` |

#### Icon Wrapper Variants

| Variant | Style |
|---|---|
| `danger` | `bg-red-100 text-red-600` |
| `warning` | `bg-amber-100 text-amber-600` |
| `info` | `bg-blue-100 text-blue-600` |

Container base: `w-[420px] rounded-xl border bg-white p-6 shadow-xl`
Overlay: `bg-black/50 backdrop-blur-sm`

---

### DropdownMenu

File: `Components/ui/DropdownMenu.tsx`

#### Props

| Prop | Type | Default |
|---|---|---|
| `trigger` | `ReactNode` | — |
| `items` | `DropdownMenuItem[]` | — |
| `position` | `'bottom-right' \| 'bottom-left' \| 'top-right' \| 'top-left'` | `'bottom-right'` |
| `onSelect` | `(item) => void` | — |

#### DropdownMenuItem

```ts
interface DropdownMenuItem {
  id: string
  label: string
  icon?: ReactNode
  tone?: 'default' | 'danger'
  disabled?: boolean
}
```

Menu styles: `rounded-xl border border-gray-100 bg-white py-1.5 shadow-lg dark:bg-slate-900`

Item tones:
- `default`: `text-gray-700 hover:bg-gray-100`
- `danger`: `text-red-600 hover:bg-red-50`

---

### UserAvatar

File: `Components/ui/UserAvatar.tsx`

User avatar — photo or initials with a color deterministically derived from the name.

#### Props

| Prop | Type | Default |
|---|---|---|
| `src` | `string` | — |
| `name` | `string` | — |
| `size` | `'sm' \| 'md' \| 'lg'` | `'md'` |
| `className` | `string` | — |

#### Sizes

| Size | Container |
|---|---|
| `sm` | `h-8 w-8` |
| `md` | `h-9 w-9 sm:h-10 sm:w-10` |
| `lg` | `h-11 w-11` |

Background color palette (12 colors): `rose`, `pink`, `fuchsia`, `purple`, `violet`, `indigo`, `blue`, `sky`, `cyan`, `teal`, `emerald`, `amber`. Selected deterministically via a hash of `name`.

---

### Logo

File: `Components/ui/Logo.tsx`

SVG lightning bolt + "Комуналка" text.

#### Props

| Prop | Type | Default |
|---|---|---|
| `size` | `'sm' \| 'md' \| 'lg'` | `'md'` |
| `showText` | `boolean` | `true` |
| `className` | `string` | — |

| Size | Icon | Text |
|---|---|---|
| `sm` | `w-5 h-6` | `text-lg` |
| `md` | `w-6 h-7` | `text-2xl` |
| `lg` | `w-8 h-10` | `text-3xl` |

SVG fill: `fill-primary stroke-primary-stroke`. Dark mode: `dark:stroke-amber-300 dark:fill-amber-300`.

---

## Forms

All form components live in `Components/ui/form/`.

### Input

File: `Components/ui/form/Input.tsx`

#### Props

| Prop | Type | Description |
|---|---|---|
| `isInvalid` | `boolean` | Error state (red border) |
| `leadingIcon` | `ReactNode` | Icon on the left |
| `endAdornment` | `ReactNode` | Element on the right |
| `wrapperClassName` | `string` | CSS class for the wrapper div |

Base styles: `w-full py-2.5 rounded-md border border-neutral-200 text-base bg-white`
Focus ring: `focus:ring-2 focus:ring-primary focus:border-primary`
Error: `border-error focus:ring-error focus:border-error`
Dark mode: `dark:border-slate-700 dark:bg-slate-800 dark:focus:border-amber-300 dark:focus:ring-amber-300`

---

### Select

File: `Components/ui/form/Select.tsx`

Native `<select>` with a custom ChevronDown arrow.

#### Props

| Prop | Type | Description |
|---|---|---|
| `isInvalid` | `boolean` | Error state |
| `leadingIcon` | `ReactNode` | Icon on the left |
| `hasLeadingIcon` | `boolean` | Enables padding offset for the icon |

Styles mirror Input.

---

### Textarea

File: `Components/ui/form/Textarea.tsx`

#### Props

| Prop | Type | Default |
|---|---|---|
| `isInvalid` | `boolean` | `false` |
| `rows` | `number` | `4` |

Non-resizable (`resize-none`). Styles mirror Input.

---

### Checkbox

File: `Components/ui/form/Checkbox.tsx`

Standard `<input type="checkbox">`.

Styles: `h-4 w-4 rounded border-gray-400 bg-white text-primary accent-primary`
Focus: `focus:ring-1 focus:ring-primary`

---

### Label

File: `Components/ui/form/Label.tsx`

#### Props

| Prop | Type | Description |
|---|---|---|
| `isRequired` | `boolean` | Appends a red `*` after the text |

Base styles: `block text-sm font-medium text-neutral-800 dark:text-slate-100`
Required: `after:ml-0.5 after:text-red-500 after:content-['*']`

---

### RadioCard

File: `Components/ui/form/RadioCard.tsx`

Button-card for selecting options (radio input equivalent).

#### Props

| Prop | Type | Description |
|---|---|---|
| `title` | `string` | Option name |
| `description` | `string` | Option description |
| `icon` | `LucideIcon \| ReactNode` | Icon |
| `selected` | `boolean` | Selected state |
| `helperText` | `ReactNode` | Additional text below |
| `iconClassName` | `string` | Icon CSS class |

Selected state: `border-primary bg-primary/10 dark:border-amber-300 dark:bg-amber-200/10`
Unselected: `border-gray-200 hover:border-primary/60 hover:bg-gray-50`
Icon container (selected): `border-primary bg-white text-primary`

---

### PhotoDropzone

File: `Components/ui/form/PhotoDropzone.tsx`

Drag-and-drop zone for photo uploads.

#### Props

| Prop | Type | Default |
|---|---|---|
| `id` | `string` | — |
| `fileName` | `string \| null` | — |
| `previewUrl` | `string \| null` | — |
| `emptyIcon` | `ReactNode` | — |
| `emptyTitle` | `ReactNode` | — |
| `emptyDescription` | `ReactNode` | — |
| `buttonLabel` | `string` | `'Upload photo'` |
| `clearLabel` | `string` | `'Remove photo'` |
| `onFilesSelected` | `(files: FileList \| null) => void` | — |
| `onClear` | `() => void` | — |
| `previewHeight` | `number` | `260` |
| `variant` | `'default' \| 'full'` | `'default'` |

Base zone: `min-h-[220px] rounded-2xl border border-dashed`
Drag-active: `border-primary bg-primary/10`
Hover: `hover:border-primary hover:bg-primary/5`

---

## Navigation & Layouts

### AuthenticatedLayout

File: `Layouts/AuthenticatedLayout.tsx`

Main layout for authenticated pages.

#### Props

| Prop | Type | Default |
|---|---|---|
| `pageTitle` | `string` | `'My Addresses'` |
| `pageSubtitle` | `string` | — |
| `notificationsCount` | `number` | `0` |
| `sidebarSections` | `SidebarSection[]` | — (defaults) |

#### Structure

```
<div class="flex min-h-screen bg-neutral-50 dark:bg-slate-950">
  <AuthenticatedSidebar />        ← 256px wide, hidden < lg
  <div class="flex flex-col flex-1">
    <AuthenticatedTopbar />       ← sticky, h-14 sm:h-16
    <main class="flex-1">
      <div class="max-w-screen-2xl px-3 py-4 sm:px-6">
        {flash.success && <Alert />}
        {flash.error && <Alert />}
        {children}
      </div>
    </main>
    <footer />
  </div>
</div>
```

Mobile sidebar: overlay `bg-black/40 backdrop-blur-[2px]` + drawer `max-w-[20rem] sm:max-w-xs`.

---

### AuthenticatedSidebar

File: `Components/navigation/AuthenticatedSidebar.tsx`

#### Props

| Prop | Type | Default |
|---|---|---|
| `sections` | `SidebarSection[]` | default sections |
| `variant` | `'desktop' \| 'mobile'` | `'desktop'` |
| `user` | `SidebarUser` | — |
| `onClose` | `() => void` | — |
| `onNavigate` | `() => void` | — |

#### Default Navigation Sections

**Main Menu:**
- Dashboard → `/` (exact)
- My Addresses → `/addresses`
- Meters → `/meters`
- Submit Reading → `/readings/new`
- Providers → `/providers`

**Settings:**
- Settings → `/settings`
- Help → `/help`

#### Nav Item Styles

Active: `bg-primary-bg font-medium text-text-dark dark:bg-amber-300 dark:text-slate-900`
Inactive: `text-gray-600 hover:bg-gray-100 hover:text-gray-900 dark:text-slate-300 dark:hover:bg-slate-800`

Item height: `h-10 sm:h-11 lg:h-12`
Icons: `h-4 w-4 sm:h-[18px] sm:w-[18px]`

#### SidebarItem

```ts
interface SidebarItem {
  label: string
  href: string
  icon: LucideIcon
  badge?: string
  badgeTone?: 'info' | 'primary'
  exact?: boolean
}
```

---

### AuthenticatedTopbar

File: `Components/navigation/AuthenticatedTopbar.tsx`

Sticky header with page title, notifications button, ThemeToggle, and user menu.

#### Props

| Prop | Type | Description |
|---|---|---|
| `title` | `string` | Page title |
| `subtitle` | `string` | Page subtitle |
| `notificationsCount` | `number` | Notification badge count |
| `user` | `TopbarUser` | User data |
| `onMenuToggle` | `() => void` | Mobile menu toggle callback |

Background: `bg-white/95 backdrop-blur dark:bg-slate-900/90`
Height: adaptive via padding (`py-2.5 sm:py-3 md:py-4`)

Burger button: visible only `< lg` (`lg:hidden`)

UserMenu (dropdown): visible only at `md:block`. On mobile — inside `MobileUserSection` of the sidebar.

---

## Shared TV Styles

Location: `Components/ui/styles/`

### navItem.ts

```ts
navItem = tv({
  base: 'flex items-center gap-3 px-3 py-2 rounded-lg transition-colors',
  variants: {
    state: {
      default: 'hover:bg-gray-100 dark:hover:bg-slate-800',
      active:  'bg-primary-bg dark:bg-amber-300/20',
    },
  },
})
```

### iconContainer.ts

```ts
iconContainer = tv({
  base: 'grid place-items-center shrink-0',
  variants: {
    size: { sm: 'h-8 w-8', md: 'h-9 w-9 sm:h-10 sm:w-10', lg: 'h-11 w-11', xl: 'h-12 w-12' },
    shape: { circle: 'rounded-full', square: 'rounded-lg' },
  },
  defaultVariants: { size: 'md', shape: 'circle' },
})
```

### card.ts

See [TV Variant (card.ts)](#tv-variant-cardts) above.

---

## Page Patterns

### List Page Structure

```tsx
<AuthenticatedLayout pageTitle="..." pageSubtitle="...">
  <Head title="..." />
  <Card>
    <PageSectionHeader
      title="Title"
      description="Description"
      ctaButton={{ label: 'Add New', onClick: () => router.visit('/create') }}
    />
    <CardContent>
      {/* card list or table */}
    </CardContent>
  </Card>
</AuthenticatedLayout>
```

### Form Page Structure

```tsx
<AuthenticatedLayout pageTitle="Add X" pageSubtitle="Fill in the form">
  <Head title="Add X" />
  <FormComponent {...props} />
</AuthenticatedLayout>
```

### PageSectionHeader

File: `Components/pages/PageSectionHeader.tsx`

Section heading inside a `CardHeader` with an optional CTA button.

| Prop | Type | Description |
|---|---|---|
| `title` | `ReactNode` | Section title |
| `description` | `ReactNode` | Section description |
| `withBorder` | `boolean` | Bottom border |
| `ctaButton` | `ButtonProps & { label, icon }` | Action button |

CTA button: `w-full sm:w-auto sm:min-w-[200px]` — full-width on mobile.

### Dashboard Layout

```tsx
<div class="mx-auto max-w-7xl space-y-4 p-4 sm:space-y-5 sm:p-6 lg:space-y-6 lg:p-8">
  <WelcomeHeader />
  <section><ConsumptionChart /></section>
  <section class="grid lg:grid-cols-2">
    <RecentReadingsTable />
    <ExpenseDistribution />
  </section>
  <QuickActions />
</div>
```

### Entity Card (MeterCard, AddressCard)

Typical pattern:

```tsx
<div class="relative flex flex-col gap-4 rounded-xl border border-gray-100 bg-gray-50 p-4 pr-10 shadow-sm dark:border-slate-700 dark:bg-slate-800">
  {/* Dropdown menu — absolute right-3 top-3 */}
  <DropdownMenu trigger={<Button size="icon" variant="ghost">...</Button>} items={ACTIONS} />
  {/* Content */}
  <div class="space-y-2">
    <p class="text-base font-semibold">{name}</p>
    <p class="text-sm text-gray-600">Field: <span class="font-medium text-gray-800">{value}</span></p>
  </div>
</div>
```

### Inline Status Badge (in entity cards)

Local `tv()` without importing the global Badge:

```ts
statusBadge = tv({
  base: 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
  variants: {
    active: {
      true: 'bg-emerald-50 text-emerald-700 border border-emerald-200',
      false: 'bg-gray-50 text-gray-600 border border-gray-200',
    },
  },
})
```

---

## Icons

Library: **Lucide React** (`lucide-react`).

### Icons in Use

| Icon | Context |
|---|---|
| `LayoutDashboard` | Dashboard (sidebar) |
| `MapPin` | Addresses |
| `Gauge` | Meters |
| `FilePlus` | Submit reading |
| `Plug` | Providers |
| `Settings` | Settings |
| `HelpCircle` | Help |
| `LogOut` | Sign out |
| `User` | Profile |
| `Bell` | Notifications |
| `Menu` | Burger menu |
| `X` | Close |
| `ChevronDown` | Dropdown arrow |
| `MoreVertical` | Three-dot menu (entity card actions) |
| `Pencil` | Edit |
| `Trash2` | Delete |
| `AlertTriangle` | Warning / ConfirmDialog |
| `AlertCircle` | Danger Alert |
| `CheckCircle2` | Success Alert |
| `Info` | Info Alert |

### Icon Sizes

| Context | Class |
|---|---|
| In inputs / small buttons | `h-4 w-4` |
| Navigation (mobile) | `h-4 w-4 sm:h-[18px] sm:w-[18px]` |
| Alert, ConfirmDialog | `h-4 w-4` / `h-6 w-6` |
| Notification, menu | `h-4 w-4 sm:h-5 sm:w-5` |
| RadioCard | `h-6 w-6` (default) |

---

## Custom Icons

Location: `Components/ui/icons/`

| File | Icon |
|---|---|
| `GoogleIcon.tsx` | Google |
| `GithubIcon.tsx` | GitHub |
| `FacebookIcon.tsx` | Facebook |
| `AppleIcon.tsx` | Apple |

Used on social authentication pages.

---

## Responsiveness (Breakpoints)

Standard Tailwind breakpoints:

| Prefix | px |
|---|---|
| `sm:` | 640px |
| `md:` | 768px |
| `lg:` | 1024px |
| `xl:` | 1280px |
| `2xl:` | 1536px |

### Key Responsive Patterns

| Element | Mobile | Desktop |
|---|---|---|
| Sidebar | Hidden, slide-in drawer | `w-64 flex` |
| Topbar burger | Visible (`lg:hidden`) | Hidden |
| User menu | Inside mobile sidebar | In topbar (`md:block`) |
| Content area | `px-3 py-4` | `sm:px-6 lg:py-6` |
| PageSectionHeader CTA | `w-full` | `sm:w-auto sm:min-w-[200px]` |
| Dashboard grid | Single column | `lg:grid-cols-2` |

---

## Animations

Tailwind Animate (animate-in/fade):
- `animate-in fade-in-0 zoom-in-95 duration-150` — DropdownMenu
- `animate-in fade-in-0 duration-200` — ConfirmDialog overlay
- `animate-in fade-in-0 zoom-in-95 duration-200` — ConfirmDialog container
- `transition-colors duration-150` — Button
- `transition-transform` — ChevronDown in UserMenu (rotates when open)

---

## Code Quality Standards

- All `.tsx` files use TypeScript strict mode
- Props defined via `interface` (not `type`)
- No `any` — explicit types or `unknown` with narrowing
- `tv()` for all variant-based styles
- `forwardRef` required for interactive form elements
- Named exports (not default) for all components

---

*Last updated: 2026-06-09*
