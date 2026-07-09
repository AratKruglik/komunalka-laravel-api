import { useState } from 'react';
import { Sun, Moon, Monitor } from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    Select,
} from '@/Components/ui';
import { useTheme } from '@/Components/theme/ThemeProvider';
import { ThemeCard } from './ThemeCard';
import type { LucideIcon } from 'lucide-react';

type ThemePreference = 'light' | 'dark' | 'system';

interface ThemeOption {
    value: ThemePreference;
    icon: LucideIcon;
    label: string;
    description: string;
}

const THEME_OPTIONS: ThemeOption[] = [
    { value: 'light', icon: Sun, label: 'Світла', description: 'Завжди світла тема' },
    { value: 'dark', icon: Moon, label: 'Темна', description: 'Завжди темна тема' },
    { value: 'system', icon: Monitor, label: 'Системна', description: 'Відповідно до налаштувань пристрою' },
];

const LANGUAGE_OPTIONS = [
    { value: 'uk', label: 'Українська' },
];

function getStoredLanguage(): string {
    try {
        return localStorage.getItem('language') ?? 'uk';
    } catch {
        return 'uk';
    }
}

export function AppearanceTab() {
    const { theme, setTheme } = useTheme();
    const [language, setLanguage] = useState(getStoredLanguage);

    const handleLanguageChange = (value: string) => {
        setLanguage(value);
        try {
            localStorage.setItem('language', value);
        } catch {
            // localStorage may be unavailable
        }
    };

    return (
        <div className="space-y-6">
            <Card className="shadow-lg">
                <CardHeader>
                    <CardTitle>Тема оформлення</CardTitle>
                    <CardDescription>Оберіть тему для інтерфейсу</CardDescription>
                </CardHeader>
                <CardContent>
                    <div
                        className="grid grid-cols-1 gap-3 sm:grid-cols-3"
                        role="radiogroup"
                        aria-label="Тема оформлення"
                    >
                        {THEME_OPTIONS.map((option) => (
                            <ThemeCard
                                key={option.value}
                                icon={option.icon}
                                label={option.label}
                                description={option.description}
                                isSelected={theme === option.value}
                                onClick={() => setTheme(option.value)}
                            />
                        ))}
                    </div>
                </CardContent>
            </Card>

            <Card className="shadow-lg">
                <CardHeader>
                    <CardTitle>Мова інтерфейсу</CardTitle>
                    <CardDescription>Оберіть мову відображення</CardDescription>
                </CardHeader>
                <CardContent className="space-y-3">
                    <Select
                        value={language}
                        onChange={(e) => handleLanguageChange(e.target.value)}
                        aria-label="Мова інтерфейсу"
                    >
                        {LANGUAGE_OPTIONS.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </Select>
                    <p className="text-xs text-text-muted">
                        Наразі доступна лише українська мова
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}
