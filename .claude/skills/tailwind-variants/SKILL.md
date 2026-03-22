---
name: tailwind-variants
description: "Tailwind Variants (tv()) patterns for component styling with variants, slots, compound variants, and responsive design. Tailwind CSS 4 compatible.\n\nTrigger words — EN: component styles, Tailwind variants, tv(), slots, responsive variants, compound variants, style composition, variant API, button variants, card styles.\nTrigger words — UA: стилі компонентів, Tailwind варіанти, tv(), слоти, responsive, компонування стилів, compound variants, варіанти кнопки, стилі картки."
---

# Tailwind Variants Patterns

## Basic tv() Pattern

Use `tv()` for all reusable component styles with variant support.

```tsx
import { tv } from 'tailwind-variants';

const button = tv({
    base: 'rounded-lg font-medium transition-colors focus:outline-none focus:ring-2',
    variants: {
        color: {
            primary: 'bg-blue-600 text-white hover:bg-blue-700 focus:ring-blue-500',
            secondary: 'bg-gray-200 text-gray-800 hover:bg-gray-300 focus:ring-gray-400',
            danger: 'bg-red-600 text-white hover:bg-red-700 focus:ring-red-500',
        },
        size: {
            sm: 'px-3 py-1.5 text-sm',
            md: 'px-4 py-2 text-base',
            lg: 'px-6 py-3 text-lg',
        },
        disabled: {
            true: 'opacity-50 cursor-not-allowed',
        },
    },
    defaultVariants: {
        color: 'primary',
        size: 'md',
    },
});

// Usage
<button className={button({ color: 'primary', size: 'lg' })}>Save</button>
<button className={button({ color: 'danger', disabled: true })}>Delete</button>
```

## Slots Pattern

Use slots for compound components with multiple styled elements.

```tsx
import { tv } from 'tailwind-variants';

const card = tv({
    slots: {
        base: 'rounded-xl border border-gray-200 bg-white shadow-sm',
        header: 'border-b border-gray-100 px-6 py-4',
        title: 'text-lg font-semibold text-gray-900',
        body: 'px-6 py-4',
        footer: 'border-t border-gray-100 px-6 py-4',
    },
    variants: {
        elevated: {
            true: {
                base: 'shadow-lg',
            },
        },
        compact: {
            true: {
                header: 'px-4 py-2',
                body: 'px-4 py-2',
                footer: 'px-4 py-2',
            },
        },
    },
});

// Usage
const { base, header, title, body, footer } = card({ elevated: true });

<div className={base()}>
    <div className={header()}>
        <h3 className={title()}>Meter Reading</h3>
    </div>
    <div className={body()}>Content here</div>
    <div className={footer()}>Actions here</div>
</div>
```

## Compound Variants

Apply styles when multiple variant conditions are met simultaneously.

```tsx
import { tv } from 'tailwind-variants';

const badge = tv({
    base: 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
    variants: {
        color: {
            success: 'bg-green-100 text-green-800',
            warning: 'bg-yellow-100 text-yellow-800',
            error: 'bg-red-100 text-red-800',
        },
        outlined: {
            true: 'bg-transparent border',
        },
    },
    compoundVariants: [
        { color: 'success', outlined: true, className: 'border-green-500 text-green-700' },
        { color: 'warning', outlined: true, className: 'border-yellow-500 text-yellow-700' },
        { color: 'error', outlined: true, className: 'border-red-500 text-red-700' },
    ],
    defaultVariants: {
        color: 'success',
    },
});
```

## Responsive Variants

Apply different variant values at different breakpoints.

```tsx
import { tv } from 'tailwind-variants';

const grid = tv({
    base: 'grid gap-4',
    variants: {
        columns: {
            1: 'grid-cols-1',
            2: 'grid-cols-2',
            3: 'grid-cols-3',
            4: 'grid-cols-4',
        },
    },
    defaultVariants: {
        columns: 1,
    },
});

// Responsive usage via Tailwind CSS 4 classes
<div className={grid({ columns: 1, className: 'md:grid-cols-2 lg:grid-cols-3' })}>
    {items.map((item) => <Card key={item.id} {...item} />)}
</div>
```

## Extending Variants

Compose variants by extending existing definitions.

```tsx
import { tv } from 'tailwind-variants';

const baseInput = tv({
    base: 'w-full rounded-lg border px-3 py-2 text-sm transition-colors',
    variants: {
        state: {
            default: 'border-gray-300 focus:border-blue-500 focus:ring-blue-500',
            error: 'border-red-500 focus:border-red-500 focus:ring-red-500',
            success: 'border-green-500 focus:border-green-500 focus:ring-green-500',
        },
    },
    defaultVariants: {
        state: 'default',
    },
});

const textarea = tv({
    extend: baseInput,
    base: 'min-h-[100px] resize-y',
});
```

## Constraints

- Use `tv()` for all reusable component styles — no inline className concatenation
- Define variants for any style that changes based on state or props
- Use slots for compound components (card, modal, table)
- Use compound variants instead of conditional className logic
- Follow Tailwind CSS 4 syntax (no `@apply` in components)
- Export variant definitions for shared usage across components
