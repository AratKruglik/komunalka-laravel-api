---
name: inertia-react-development
description: >-
    Inertia v3 + React 19 canonical code examples: component structure with tv(),
    useForm patterns, Deferred/WhenVisible, shared props via usePage(), Partial Reloads,
    SSR caveats, and Redirects. Activate when building or reviewing React components,
    Inertia pages, or frontend forms in this project.

    Українською: компоненти React, форми з useForm, Inertia-сторінки, usePage, shared props,
    Deferred, WhenVisible, Partial Reloads, редіректи Inertia, SSR, tv() tailwind-variants.
---

# Inertia v3 + React 19 — Canonical Examples

## Component Structure

Full component with `tv()` variants, typed props, named export:

```tsx
import type { ReactNode } from 'react'
import { usePage, useForm } from '@inertiajs/react'
import { tv } from 'tailwind-variants'
import { route } from 'ziggy-js'

interface MeterCardProps {
    title: string
    children?: ReactNode
}

const card = tv({
    base: 'rounded-md border p-4',
    variants: {
        tone: {
            default: 'bg-white',
            warning: 'bg-amber-50 border-amber-300',
        },
    },
    defaultVariants: { tone: 'default' },
})

export function MeterCard({ title, children }: MeterCardProps): ReactNode {
    return (
        <div className={card()}>
            <h3 className="font-semibold">{title}</h3>
            {children}
        </div>
    )
}
```

## Shared Props via usePage()

```tsx
import { usePage } from '@inertiajs/react'
import type { User, Flash } from '@/types'

const page = usePage<{ auth: { user: User }; flash: Flash }>()
const user = page.props.auth.user
```

## useForm + Form Request

```tsx
import { useForm } from '@inertiajs/react'
import { route } from 'ziggy-js'

const form = useForm({ title: '', body: '' })

function submit(event: React.FormEvent): void {
    event.preventDefault()
    form.post(route('posts.store'), {
        onSuccess: () => form.reset(),
    })
}
```

Form Request validation errors map automatically to `form.errors.field`.

## Deferred Props (v3)

Backend:
```php
return Inertia::render('Dashboard', [
    'user'  => $user,
    'stats' => Inertia::defer(fn () => $stats),
]);
```

Frontend:
```tsx
import { Deferred } from '@inertiajs/react'

<Deferred data="stats" fallback={<div className="h-20 animate-pulse rounded bg-gray-200" />}>
    <StatsCard stats={stats} />
</Deferred>
```

> Inertia v3 removed `Inertia::lazy()` / `LazyProp` — use `Inertia::optional()`.

## Partial Reloads

```tsx
import { router } from '@inertiajs/react'

router.reload({ only: ['posts'] })
```

## WhenVisible (Lazy Loading)

```tsx
import { WhenVisible } from '@inertiajs/react'

<WhenVisible data="comments" fallback={<LoadingSpinner />}>
    <CommentsList />
</WhenVisible>
```

## Redirects

```php
return redirect()->route('posts.index');           // Inertia redirect
return Inertia::location(route('posts.index'));    // external/full redirect
```
