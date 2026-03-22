---
name: react-inertia
description: "React + Inertia.js v2 patterns for Laravel full-stack applications. Covers page components, form handling, layouts, navigation, shared data, and typed props.\n\nTrigger words — EN: React component, Inertia page, TypeScript component, form handling, useForm, router, Head, Link, page props, shared data, persistent layout, Inertia render, page navigation.\nTrigger words — UA: React компонент, Inertia сторінка, TypeScript компонент, форми, useForm, навігація, Head, Link, пропси сторінки, спільні дані, макет, Inertia рендер, навігація сторінок."
---

# React + Inertia.js v2 Patterns

## Page Component Pattern

Every Inertia page is a React component receiving typed props from the server.

```tsx
import { Head } from '@inertiajs/react';
import { type PageProps } from '@/types';

interface Meter {
    id: number;
    serial_number: string;
    utility_type: string;
}

interface Props extends PageProps {
    meters: {
        data: Meter[];
        links: Record<string, string | null>;
        meta: { current_page: number; last_page: number };
    };
}

export function Index({ meters }: Props) {
    return (
        <>
            <Head title="Meters" />
            <h1>My Meters</h1>
            <ul>
                {meters.data.map((meter) => (
                    <li key={meter.id}>{meter.serial_number} — {meter.utility_type}</li>
                ))}
            </ul>
        </>
    );
}
```

## Form Handling Pattern

Use `useForm` from `@inertiajs/react` for all form submissions.

```tsx
import { useForm } from '@inertiajs/react';

interface FormData {
    meter_id: number;
    value: string;
    reading_date: string;
}

export function CreateReading({ meterId }: { meterId: number }) {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        meter_id: meterId,
        value: '',
        reading_date: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post('/meter-readings');
    }

    return (
        <form onSubmit={handleSubmit}>
            <input
                type="number"
                value={data.value}
                onChange={(e) => setData('value', e.target.value)}
            />
            {errors.value && <span>{errors.value}</span>}

            <input
                type="date"
                value={data.reading_date}
                onChange={(e) => setData('reading_date', e.target.value)}
            />
            {errors.reading_date && <span>{errors.reading_date}</span>}

            <button type="submit" disabled={processing}>
                Submit
            </button>
        </form>
    );
}
```

## Layout Pattern (Persistent)

Use persistent layouts to avoid re-mounting on every navigation.

```tsx
import { type ReactNode } from 'react';
import { Link } from '@inertiajs/react';

interface LayoutProps {
    children: ReactNode;
}

export function AppLayout({ children }: LayoutProps) {
    return (
        <div>
            <nav>
                <Link href="/dashboard">Dashboard</Link>
                <Link href="/meters">Meters</Link>
                <Link href="/billing">Billing</Link>
            </nav>
            <main>{children}</main>
        </div>
    );
}

// Usage in page component — persistent layout
Index.layout = (page: ReactNode) => <AppLayout>{page}</AppLayout>;
```

## Shared Data & Auth

Access shared data (authenticated user, flash messages) via `usePage()`.

```tsx
import { usePage } from '@inertiajs/react';
import { type PageProps } from '@/types';

interface SharedProps extends PageProps {
    auth: {
        user: { id: number; name: string; email: string };
    };
    flash: {
        success?: string;
        error?: string;
    };
}

export function UserNav() {
    const { auth, flash } = usePage<SharedProps>().props;

    return (
        <div>
            <span>{auth.user.name}</span>
            {flash.success && <div className="text-green-600">{flash.success}</div>}
        </div>
    );
}
```

## Navigation

Use `Link` for declarative navigation and `router` for programmatic.

```tsx
import { Link, router } from '@inertiajs/react';

// Declarative
<Link href="/meters" className="text-blue-600">View Meters</Link>
<Link href="/meters" method="post" data={{ meter_id: 1 }}>Create</Link>

// Programmatic
router.visit('/meters');
router.post('/meter-readings', { value: 150.5 });
router.reload({ only: ['meters'] });
```

## Server-Side (Laravel Page Action)

```php
<?php

declare(strict_types=1);

namespace Modules\Meter\Actions;

use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsController;
use Modules\Meter\Models\Meter;

class ListMetersPage
{
    use AsController;

    public function handle(): Response
    {
        $meters = Meter::query()
            ->where('user_id', auth()->id())
            ->with(['address:id,street,building', 'latestReading'])
            ->orderByDesc('created_at')
            ->paginate();

        return Inertia::render('Meters/Index', [
            'meters' => $meters,
        ]);
    }
}
```

## Constraints

- All page components in `.tsx` files
- Always type props via `interface` extending `PageProps`
- Always use `useForm` for form handling (not manual fetch/axios)
- Always include `Head` component for page title
- Use persistent layouts to prevent unnecessary re-mounts
- Named exports for all components (not default exports)
