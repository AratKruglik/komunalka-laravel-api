import { tv } from 'tailwind-variants';
import type { LucideIcon } from 'lucide-react';

interface ThemeCardProps {
    icon: LucideIcon;
    label: string;
    description: string;
    isSelected: boolean;
    onClick: () => void;
}

const themeCard = tv({
    base: [
        'flex w-full cursor-pointer flex-col items-center gap-3 rounded-xl border-2 p-5',
        'text-center transition-all duration-150',
        'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary',
    ],
    variants: {
        selected: {
            true: 'border-primary bg-primary/5 shadow-sm',
            false: 'border-line bg-raised hover:border-text-muted hover:bg-surface',
        },
    },
    defaultVariants: { selected: false },
});

export function ThemeCard({ icon: Icon, label, description, isSelected, onClick }: ThemeCardProps) {
    return (
        <button
            type="button"
            role="radio"
            aria-checked={isSelected}
            onClick={onClick}
            className={themeCard({ selected: isSelected })}
        >
            <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-surface text-subtext">
                <Icon className="h-5 w-5" strokeWidth={1.7} />
            </span>
            <div>
                <span className="block text-sm font-semibold text-foreground">
                    {label}
                </span>
                <span className="mt-0.5 block text-xs text-muted">
                    {description}
                </span>
            </div>
        </button>
    );
}
