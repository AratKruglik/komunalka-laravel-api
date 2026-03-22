import { Palette, Settings, Shield, User } from 'lucide-react';
import { tv } from 'tailwind-variants';
import type { LucideIcon } from 'lucide-react';

type SettingsTab = 'profile' | 'security' | 'appearance' | 'account';

interface TabConfig {
    id: SettingsTab;
    label: string;
    icon: LucideIcon;
    description: string;
}

const TABS: TabConfig[] = [
    { id: 'profile', label: 'Профіль', icon: User, description: 'Особисті дані' },
    { id: 'security', label: 'Безпека', icon: Shield, description: 'Пароль та акаунти' },
    { id: 'appearance', label: 'Зовнішній вигляд', icon: Palette, description: 'Тема та мова' },
    { id: 'account', label: 'Акаунт', icon: Settings, description: 'Експорт та видалення' },
];

const tabItem = tv({
    base: [
        'flex items-center gap-3 rounded-lg px-3 py-2.5',
        'cursor-pointer transition-colors',
    ],
    variants: {
        active: {
            true: [
                'bg-primary-bg font-medium text-text-dark',
                'dark:bg-amber-300/20 dark:text-amber-200',
            ],
            false: [
                'text-gray-600 hover:bg-gray-100 hover:text-gray-900',
                'dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white',
            ],
        },
    },
    defaultVariants: { active: false },
});

interface SettingsSidebarProps {
    activeTab: SettingsTab;
    onTabChange: (tab: SettingsTab) => void;
}

export function SettingsSidebar({ activeTab, onTabChange }: SettingsSidebarProps) {
    return (
        <>
            <nav
                className="hidden rounded-xl border border-gray-200 bg-white p-2 dark:border-slate-800 dark:bg-slate-900 md:block"
                aria-label="Розділи налаштувань"
            >
                <ul className="space-y-1" role="tablist">
                    {TABS.map((tab) => {
                        const Icon = tab.icon;
                        const isActive = activeTab === tab.id;

                        return (
                            <li key={tab.id} role="presentation">
                                <button
                                    type="button"
                                    role="tab"
                                    aria-selected={isActive}
                                    className={`${tabItem({ active: isActive })} w-full text-left`}
                                    onClick={() => onTabChange(tab.id)}
                                >
                                    <Icon className="h-5 w-5 shrink-0" />
                                    <div className="min-w-0">
                                        <span className="block text-sm leading-5">{tab.label}</span>
                                        <span className="block text-xs leading-4 text-gray-400 dark:text-slate-500">
                                            {tab.description}
                                        </span>
                                    </div>
                                </button>
                            </li>
                        );
                    })}
                </ul>
            </nav>

            <nav className="overflow-x-auto md:hidden" aria-label="Розділи налаштувань">
                <ul
                    className="flex gap-1 rounded-xl border border-gray-200 bg-white p-1.5 dark:border-slate-800 dark:bg-slate-900"
                    role="tablist"
                >
                    {TABS.map((tab) => {
                        const Icon = tab.icon;
                        const isActive = activeTab === tab.id;

                        return (
                            <li key={tab.id} role="presentation" className="shrink-0">
                                <button
                                    type="button"
                                    role="tab"
                                    aria-selected={isActive}
                                    className={`${tabItem({ active: isActive })} flex-nowrap whitespace-nowrap px-3 py-2`}
                                    onClick={() => onTabChange(tab.id)}
                                >
                                    <Icon className="h-4 w-4 shrink-0" />
                                    <span className="text-sm">{tab.label}</span>
                                </button>
                            </li>
                        );
                    })}
                </ul>
            </nav>
        </>
    );
}
