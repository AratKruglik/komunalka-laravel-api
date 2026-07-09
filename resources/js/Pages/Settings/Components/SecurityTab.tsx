import { useState, useCallback } from 'react';
import { useForm, router } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import {
    Button,
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    ConfirmDialog,
    Input,
    PasswordInput,
} from '@/Components/ui';
import { ConnectedAccountCard } from './ConnectedAccountCard';

interface ConnectedProvider {
    provider: string;
    is_connected: boolean;
}

interface SecurityTabProps {
    connectedProviders: ConnectedProvider[];
}

export function SecurityTab({ connectedProviders }: SecurityTabProps) {
    const passwordForm = useForm({
        current_password: '',
        new_password: '',
        new_password_confirmation: '',
    });

    const [pendingUnlink, setPendingUnlink] = useState<string | null>(null);
    const [unlinkPassword, setUnlinkPassword] = useState('');
    const [isUnlinking, setIsUnlinking] = useState(false);

    const handlePasswordSubmit = (event: React.FormEvent) => {
        event.preventDefault();
        passwordForm.put(route('settings.password'), {
            preserveScroll: true,
            onSuccess: () => passwordForm.reset(),
        });
    };

    const handleConnect = (provider: string) => {
        window.location.href = route('settings.oauth.link', { provider });
    };

    const handleDisconnect = (provider: string) => {
        setPendingUnlink(provider);
        setUnlinkPassword('');
    };

    const handleConfirmUnlink = useCallback(() => {
        if (!pendingUnlink || !unlinkPassword) {
            return;
        }

        setIsUnlinking(true);
        router.delete(route('settings.oauth.unlink', { provider: pendingUnlink }), {
            data: { password: unlinkPassword },
            preserveScroll: true,
            onFinish: () => {
                setIsUnlinking(false);
                setPendingUnlink(null);
                setUnlinkPassword('');
            },
        });
    }, [pendingUnlink, unlinkPassword]);

    const handleCancelUnlink = useCallback(() => {
        setPendingUnlink(null);
        setUnlinkPassword('');
    }, []);

    return (
        <div className="space-y-6">
            <form onSubmit={handlePasswordSubmit}>
                <Card className="shadow-lg">
                    <CardHeader>
                        <CardTitle>Зміна паролю</CardTitle>
                        <CardDescription>
                            Оновіть пароль для захисту вашого акаунту
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-5">
                        <PasswordInput
                            id="current_password"
                            label="Поточний пароль"
                            value={passwordForm.data.current_password}
                            onChange={(value) => passwordForm.setData('current_password', value)}
                            disabled={passwordForm.processing}
                            error={passwordForm.errors.current_password}
                            showStrength={false}
                            autoComplete="current-password"
                            placeholder="--------"
                        />

                        <PasswordInput
                            id="new_password"
                            label="Новий пароль"
                            value={passwordForm.data.new_password}
                            onChange={(value) => passwordForm.setData('new_password', value)}
                            disabled={passwordForm.processing}
                            error={passwordForm.errors.new_password}
                            autoComplete="new-password"
                            placeholder="--------"
                        />

                        <PasswordInput
                            id="new_password_confirmation"
                            label="Підтвердити новий пароль"
                            value={passwordForm.data.new_password_confirmation}
                            onChange={(value) => passwordForm.setData('new_password_confirmation', value)}
                            disabled={passwordForm.processing}
                            error={passwordForm.errors.new_password_confirmation}
                            showStrength={false}
                            autoComplete="new-password"
                            placeholder="--------"
                        />

                        <div className="flex justify-end pt-2">
                            <Button
                                type="submit"
                                loading={passwordForm.processing}
                                loadingText="Збереження..."
                            >
                                Змінити пароль
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </form>

            <Card className="shadow-lg">
                <CardHeader>
                    <CardTitle>Під&apos;єднані акаунти</CardTitle>
                    <CardDescription>
                        Керуйте способами входу у ваш акаунт
                    </CardDescription>
                </CardHeader>
                <CardContent className="space-y-3">
                    {connectedProviders.map((account) => (
                        <ConnectedAccountCard
                            key={account.provider}
                            provider={account.provider}
                            isConnected={account.is_connected}
                            onConnect={() => handleConnect(account.provider)}
                            onDisconnect={() => handleDisconnect(account.provider)}
                        />
                    ))}
                </CardContent>
            </Card>

            <ConfirmDialog
                isOpen={pendingUnlink !== null}
                onClose={handleCancelUnlink}
                onConfirm={handleConfirmUnlink}
                title={`Від'єднати ${pendingUnlink ?? ''}?`}
                description={
                    <div className="w-full space-y-3">
                        <span>Введіть пароль для підтвердження</span>
                        <Input
                            type="password"
                            value={unlinkPassword}
                            onChange={(e) => setUnlinkPassword(e.target.value)}
                            placeholder="Ваш пароль"
                            leadingIcon={<Lock className="h-4 w-4 text-text-muted" />}
                            autoFocus
                        />
                    </div>
                }
                confirmLabel="Від'єднати"
                variant="danger"
                isLoading={isUnlinking}
            />
        </div>
    );
}
