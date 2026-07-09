# DesignBook — Komunalka (ElevenLabs Inspired)

Complete design system reference for the project. Use as a single source of truth when working on the UI.
This design system is inspired by ElevenLabs: a stark, minimal identity using pure black and white with golden yellow as the sole accent. We have adapted this philosophy to support both **Light** and **Dark** modes.

---

## Table of Contents

1. [Visual Theme & Atmosphere](#visual-theme--atmosphere)
2. [Tech Stack](#tech-stack)
3. [Color Palette](#color-palette)
4. [Dark Mode Implementation](#dark-mode-implementation)
5. [Typography](#typography)
6. [Spacing & Sizing](#spacing--sizing)
7. [Depth & Elevation](#depth--elevation)
8. [UI Components](#ui-components)
9. [Navigation & Layouts](#navigation--layouts)
10. [Forms](#forms)
11. [Page Patterns](#page-patterns)
12. [Icons](#icons)

---

## Visual Theme & Atmosphere

- **Extreme minimalism** — zero decorative elements, letting content be the hero.
- **Audio/Content-first** — keep the interface out of the way. (Can be adapted to Data-first for Komunalka).
- **Dual Themes** — explicit support for both stark Light and pure Dark modes, maintaining high contrast.

---

## Tech Stack

| Technology | Version | Purpose |
|---|---|---|
| Tailwind CSS | v4 | Utility-first CSS framework |
| Tailwind Variants (`tv()`) | — | Component variant system |
| React | 19 | UI components (`.tsx`) |
| Inertia.js | v2 | SPA with server-driven routing |
| Lucide React | — | Icon library |

---

## Color Palette

Colors are declared as CSS Custom Properties in `resources/css/app.css` via `@theme {}`.

### Primary Colors (Brand Accent - Same for both modes)

| Variable | HEX | Description |
|---|---|---|
| `--color-primary` | `#F5C542` | Brand Gold (active states, selected states, primary buttons) |
| `--color-primary-dark` | `#D4A820` | Gold Dark (hover state) |

### Surface & Backgrounds

| Variable | Light Mode | Dark Mode | Usage |
|---|---|---|---|
| `--color-bg-primary` | `#FFFFFF` (White) | `#000000` (Pure Black) | App background |
| `--color-bg-surface` | `#F9FAFB` (Gray-50) | `#1A1A1A` (Dark Gray) | Cards, panels, sidebars |
| `--color-bg-raised` | `#FFFFFF` | `#252525` (Lighter Gray) | Input fields, raised elements |

### Text Colors

| Variable | Light Mode | Dark Mode | Usage |
|---|---|---|---|
| `--color-text-primary` | `#000000` | `#FFFFFF` | Primary text (Headings, body) |
| `--color-text-secondary` | `#6B7280` | `#888888` | Secondary text (Labels, metadata) |
| `--color-text-muted` | `#9CA3AF` | `#555555` | Muted text (Placeholders, inactive elements) |
| `--color-text-inverse` | `#000000` | `#000000` | Inverse text (Used on Gold backgrounds, e.g., Primary Buttons) |

### Border Colors

| Variable | Light Mode | Dark Mode | Usage |
|---|---|---|---|
| `--color-border` | `#E5E7EB` | `#2E2E2E` | Default borders and dividers |

### Semantic / Status (Same for both modes)

| Variable | HEX | Usage |
|---|---|---|
| `--color-playing` | `#F5C542` | Active state (uses brand gold) |
| `--color-success` | `#22C55E` | Success, positive actions |
| `--color-error` | `#EF4444` | Errors, failed actions |
| `--color-processing` | `#60A5FA` | In-progress, processing |

---

## Dark Mode Implementation (UX Pro OLED Style)

Dark mode is toggled via the `data-theme="dark"` attribute on `:root`.
Tailwind dark variant `dark:` is activated via the `[data-theme=dark]` selector.

Our dark mode follows **OLED and Minimalist** UX guidelines (high readability, low light emission, minimal glow).
- **True Black Focus:** Use deep black (`#000000`) for the primary background to leverage OLED power efficiency and create a stark contrast with the brand Gold.
- **Elevation via Lightness:** Use `#1A1A1A` for elevated surfaces (cards, sidebars). 
- **Text Contrast:** Avoid pure white for large blocks of body text to reduce eye strain; use `#888888` or `#E5E7EB` for secondary information.
- **Interaction:** Maintain visible focus rings and use smooth transitions (150-300ms) for hover states (e.g., `transition-colors duration-200`).

Common dark mode equivalents:

| Element | Light Mode | Dark Mode (OLED) |
|---|---|---|
| **Background** | `bg-[#FFFFFF]` | `dark:bg-[#000000]` |
| **Surface** | `bg-[#F9FAFB]` | `dark:bg-[#1A1A1A]` |
| **Borders** | `border-[#E5E7EB]` | `dark:border-[#2E2E2E]` |
| **Primary Text** | `text-[#000000]` | `dark:text-[#FFFFFF]` |
| **Secondary Text**| `text-[#6B7280]` | `dark:text-[#888888]` |

---

## Typography

### Font Family

Primary: **Inter**, fallback: `system-ui, sans-serif`

### Hierarchy & Scale

| Role | Size | Weight | Line Height | Letter Spacing | Context |
|------|------|--------|-------------|----------------|---------|
| Display | 52px (`text-[52px]`) | 700 | 1.1 | -0.02em | Marketing hero |
| H1 | 36px (`text-4xl`) | 700 | 1.2 | -0.01em | Page titles |
| H2 | 24px (`text-2xl`) | 600 | 1.3 | 0 | Section headings |
| H3 | 18px (`text-lg`) | 600 | 1.4 | 0 | Card titles |
| Body | 16px (`text-base`) | 400 | 1.6 | 0 | Prose content |
| Label | 13px (`text-[13px]`) | 500 | 1.4 | 0.02em | Precision labels |
| Caption | 12px (`text-xs`) | 400 | 1.4 | 0 | Timestamps, minor meta |

**Principles:**
- Never use Gold for body text (insufficient contrast).
- Gold CTA text must always be black (`#000000`), regardless of the theme.

---

## Spacing & Sizing

### Spacing System

We use the standard Tailwind CSS spacing system to ensure consistency across the application.

| Variable | rem | px | Usage |
|---|---|---|---|
| `--spacing-xs` | `0.25rem` | 4px | Tight spacing, track padding |
| `--spacing-sm` | `0.5rem` | 8px | Control spacing, small gaps |
| `--spacing-md` | `1rem` | 16px | Card padding (standard) |
| `--spacing-lg` | `1.5rem` | 24px | Section gaps, extended padding |
| `--spacing-xl` | `2rem` | 32px | Feature blocks |
| `--spacing-2xl` | `3rem` | 48px | Page sections |
| `--spacing-3xl` | `4rem` | 64px | Large page sections |

### Border Radius Scale

| Level | Value | Usage |
|---|---|---|
| **None** | `0px` (`rounded-none`) | Timelines, full-width tracks |
| **Sm** | `4px` (`rounded-sm`) | Badges, tags |
| **Md** | `6px` (`rounded-md`) | Buttons, inputs |
| **Lg** | `8px` (`rounded-lg`) | Cards, panels |
| **Full** | `9999px` (`rounded-full`) | Circular play/stop buttons, avatars |

---

## Depth & Elevation

| Level | Light Mode Shadow | Dark Mode Shadow / Border | Usage |
|-------|-------------------|---------------------------|-------|
| **Flat** | `none` | `none` | Standard canvas elements |
| **Raised** | `0 1px 2px rgba(0,0,0,0.05)` | `0 0 0 1px rgba(255,255,255,0.06)` | Cards, panels |
| **Overlay** | `0 4px 6px rgba(0,0,0,0.1)` | `0 4px 20px rgba(0,0,0,0.8)` | Dropdowns, menus |
| **Modal** | `0 10px 15px rgba(0,0,0,0.1)` | `0 8px 40px rgba(0,0,0,0.9)` | Dialogs, ConfirmDialog |

---

## UI Components

### Button

Built with `tv()`. Radius: `6px`. Padding: `10px 20px` (for standard).

| Variant | Light Styling | Dark Styling |
|---|---|---|
| **Primary** | `bg-[#F5C542] text-[#000000] hover:bg-[#D4A820]` | `dark:bg-[#F5C542] dark:text-[#000000] dark:hover:bg-[#D4A820]` |
| **Secondary** | `bg-transparent border border-[#E5E7EB] text-[#000000] hover:border-[#9CA3AF]` | `dark:bg-transparent dark:border-[#2E2E2E] dark:text-[#FFFFFF] dark:hover:border-[#555555]` |
| **Ghost** | `bg-transparent text-[#6B7280] hover:text-[#000000] hover:bg-[#F9FAFB]` | `dark:bg-transparent dark:text-[#888888] dark:hover:text-[#FFFFFF] dark:hover:bg-[#1A1A1A]` |

Disabled state: `opacity-50 pointer-events-none`.

### Card

- Background: Light `#F9FAFB`, Dark `#1A1A1A`
- Border: Light `1px solid #E5E7EB`, Dark `1px solid #2E2E2E`
- Radius: `8px`
- Padding: `20px`

```ts
card = tv({
  base: 'rounded-lg border border-[#E5E7EB] bg-[#F9FAFB] p-[20px] dark:border-[#2E2E2E] dark:bg-[#1A1A1A]',
})
```

---

## Forms

### Input & Textarea

- Background: Light `#FFFFFF`, Dark `#252525`
- Border: Light `1px solid #E5E7EB`, Dark `1px solid #2E2E2E`
- Radius: `6px`
- Padding: `10px 14px`
- Text: Light `#000000`, Dark `#FFFFFF`
- Focus: `border-[#F5C542] ring-1 ring-[#F5C542]` (Same for both)

### Label

- Text: `13px` (`text-[13px]`)
- Weight: `500`
- Color: Light `#6B7280`, Dark `#888888`
- Letter-spacing: `0.02em`

---

## Navigation & Layouts

### Layout Structure

- **Sidebar**: `240px` wide, background Light `#F9FAFB`, Dark `#000000`.
- **Top Nav**: `56px` height, background Light `#FFFFFF`, Dark `#000000`, border-bottom Light `1px solid #E5E7EB`, Dark `1px solid #1A1A1A`.
- **Grid & Container**: Max width `1200px`. Fluid content area alongside the 240px sidebar.

### Responsive Behavior

| Breakpoint | Width | Behavior |
|---|---|---|
| **Mobile** | `0–767px` | Single column. Sidebar collapses to icon strip or hidden drawer. |
| **Tablet** | `768–1023px` | 2-column, collapsed sidebar. |
| **Desktop** | `1024px+` | Full layout with 240px sidebar. |

**Touch Targets:** Minimum `44x44px`. Primary actions `48x48px`.

---

## Page Patterns

### Dashboard & List Layout

```tsx
<div className="flex h-screen bg-[#FFFFFF] text-[#000000] font-sans dark:bg-[#000000] dark:text-[#FFFFFF]">
  <Sidebar className="w-[240px] bg-[#F9FAFB] border-r border-[#E5E7EB] dark:bg-[#000000] dark:border-[#1A1A1A]" />
  <div className="flex-1 flex flex-col">
    <TopNav className="h-[56px] bg-[#FFFFFF] border-b border-[#E5E7EB] dark:bg-[#000000] dark:border-[#1A1A1A]" />
    <main className="flex-1 p-[48px] max-w-[1200px] w-full mx-auto">
      <SectionHeading className="mb-[24px]">
        <h1 className="text-4xl font-bold tracking-tight">Meters</h1>
        <Button variant="primary">Add New</Button>
      </SectionHeading>
      <div className="grid gap-[16px]">
        {/* Cards */}
      </div>
    </main>
  </div>
</div>
```

---

## Icons

Library: **Lucide React**.

### Sizing
- Standard: `16x16px` or `20x20px`.
- Large Actions: `24x24px`.
- Placeholders: full rounded containers (`rounded-full`).

---
