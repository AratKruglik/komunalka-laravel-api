import { tv } from 'tailwind-variants';
import { Badge, Button, GoogleIcon, GithubIcon } from '@/Components/ui';
import type { FC, SVGProps } from 'react';

interface ConnectedAccountCardProps {
    provider: string;
    isConnected: boolean;
    onConnect: () => void;
    onDisconnect: () => void;
}

const providerRow = tv({
    base: [
        'flex items-center justify-between gap-4',
        'rounded-lg border border-gray-200 bg-white p-4',
        'dark:border-slate-700 dark:bg-slate-800/50',
    ],
});

const PROVIDER_LABELS: Record<string, string> = {
    google: 'Google',
    github: 'GitHub',
};

const PROVIDER_ICONS: Record<string, FC<SVGProps<SVGSVGElement> & { size?: number }>> = {
    google: GoogleIcon,
    github: GithubIcon,
};

export function ConnectedAccountCard({
    provider,
    isConnected,
    onConnect,
    onDisconnect,
}: ConnectedAccountCardProps) {
    const Icon = PROVIDER_ICONS[provider];
    const label = PROVIDER_LABELS[provider] ?? provider;

    return (
        <div className={providerRow()}>
            <div className="flex items-center gap-3">
                {Icon ? <Icon size={20} /> : null}
                <span className="text-sm font-medium text-gray-900 dark:text-slate-100">
                    {label}
                </span>
            </div>

            <div className="flex items-center gap-3">
                <Badge variant={isConnected ? 'success' : 'neutral'}>
                    {isConnected ? "Під'єднано" : "Від'єднано"}
                </Badge>

                {isConnected ? (
                    <Button
                        variant="outline"
                        tone="danger"
                        size="sm"
                        onClick={onDisconnect}
                    >
                        Від&apos;єднати
                    </Button>
                ) : (
                    <Button
                        variant="outline"
                        tone="primary"
                        size="sm"
                        onClick={onConnect}
                    >
                        Під&apos;єднати
                    </Button>
                )}
            </div>
        </div>
    );
}
