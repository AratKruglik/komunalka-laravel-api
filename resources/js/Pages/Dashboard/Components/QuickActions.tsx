import { Link } from '@inertiajs/react';
import { Plus, Home, Receipt } from 'lucide-react';
import { tv } from 'tailwind-variants';
import type { LucideIcon } from 'lucide-react';

const card = tv({
    base: 'rounded-xl border border-border bg-bg-raised p-4 shadow-lg sm:p-5 lg:p-6',
});

const actionButton = tv({
    base: 'inline-flex h-12 w-full items-center justify-center gap-2 rounded-lg px-5 text-sm font-medium transition-colors',
    variants: {
        variant: {
            primary: 'bg-primary text-text-inverse hover:bg-primary-dark active:bg-primary-dark',
            outline: 'border border-border bg-bg-raised text-text-secondary hover:bg-bg-surface',
        },
    },
    defaultVariants: {
        variant: 'primary',
    },
});

interface QuickAction {
    id: string;
    icon: LucideIcon;
    label: string;
    href: string;
    variant: 'primary' | 'outline';
}

const actions: QuickAction[] = [
    {
        id: '1',
        icon: Plus,
        label: 'Додати показання',
        href: '/readings/create',
        variant: 'primary',
    },
    {
        id: '2',
        icon: Home,
        label: 'Додати адресу',
        href: '/addresses',
        variant: 'outline',
    },
    {
        id: '3',
        icon: Receipt,
        label: 'Переглянути тарифи',
        href: '/tariffs',
        variant: 'outline',
    },
];

export function QuickActions() {
    return (
        <section className={card()}>
            <header className="mb-3 sm:mb-4">
                <h2 className="text-base font-semibold leading-6 text-text-primary sm:text-lg sm:leading-7 lg:text-xl">
                    Швидкі дії
                </h2>
            </header>

            <div className="grid gap-3 sm:grid-cols-2 sm:gap-4 lg:grid-cols-3">
                {actions.map((action) => {
                    const Icon = action.icon;
                    return (
                        <Link key={action.id} href={action.href} className={actionButton({ variant: action.variant })}>
                            <Icon className="h-5 w-5" />
                            <span>{action.label}</span>
                        </Link>
                    );
                })}
            </div>
        </section>
    );
}
