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
            true: [
                'border-primary bg-primary/5 shadow-sm',
                'dark:border-amber-400 dark:bg-amber-400/10',
            ],
            false: [
                'border-gray-200 bg-white hover:border-gray-300 hover:bg-gray-50',
                'dark:border-slate-700 dark:bg-slate-800/50 dark:hover:border-slate-600 dark:hover:bg-slate-800',
            ],
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
            <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-100 text-gray-700 dark:bg-slate-700 dark:text-slate-200">
                <Icon className="h-5 w-5" strokeWidth={1.7} />
            </span>
            <div>
                <span className="block text-sm font-semibold text-gray-900 dark:text-slate-100">
                    {label}
                </span>
                <span className="mt-0.5 block text-xs text-gray-500 dark:text-slate-400">
                    {description}
                </span>
            </div>
        </button>
    );
}
