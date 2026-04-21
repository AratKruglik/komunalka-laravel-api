import { useState } from 'react';
import { useForm } from '@inertiajs/react';
import { tv } from 'tailwind-variants';
import {
    Button,
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
    ConfirmDialog,
    FormMessage,
    Input,
} from '@/Components/ui';
import { Download, Lock } from 'lucide-react';

const dangerZone = tv({
    base: [
        'rounded-xl border-2 border-red-200 bg-red-50 p-4',
        'dark:border-red-800/50 dark:bg-red-950/20',
        'sm:p-5 lg:p-6',
    ],
});

export function AccountTab() {
    const [isConfirmOpen, setIsConfirmOpen] = useState(false);
    const [password, setPassword] = useState('');

    const deleteForm = useForm({
        password: '',
    });

    const handleConfirmDelete = () => {
        deleteForm.setData('password', password);
        deleteForm.delete(route('settings.account'), {
            onFinish: () => {
                setIsConfirmOpen(false);
                setPassword('');
            },
        });
    };

    const handleOpenConfirm = () => {
        setPassword('');
        setIsConfirmOpen(true);
    };

    const handleCloseConfirm = () => {
        setIsConfirmOpen(false);
        setPassword('');
        deleteForm.clearErrors();
    };

    return (
        <div className="space-y-6">
            <Card className="border border-gray-200 shadow-lg dark:border-slate-800">
                <CardHeader>
                    <CardTitle>Експорт даних</CardTitle>
                    <CardDescription>Завантажте копію ваших даних</CardDescription>
                </CardHeader>
                <CardContent>
                    <Button variant="outline" tone="primary">
                        <Download className="h-4 w-4" />
                        Експортувати дані (CSV)
                    </Button>
                </CardContent>
            </Card>

            <div className={dangerZone()}>
                <h3 className="text-lg font-semibold text-red-700 dark:text-red-400">
                    Небезпечна зона
                </h3>
                <p className="mt-1 text-sm text-red-600/80 dark:text-red-300/70">
                    Видалення акаунта призведе до безповоротної втрати всіх ваших даних, включаючи
                    адреси, лічильники та показники.
                </p>

                {deleteForm.errors.password ? (
                    <div className="mt-4">
                        <FormMessage variant="error">{deleteForm.errors.password}</FormMessage>
                    </div>
                ) : null}

                <Button
                    variant="outline"
                    tone="danger"
                    className="mt-4"
                    onClick={handleOpenConfirm}
                >
                    Видалити акаунт
                </Button>
            </div>

            <ConfirmDialog
                isOpen={isConfirmOpen}
                onClose={handleCloseConfirm}
                onConfirm={handleConfirmDelete}
                variant="danger"
                title="Видалити акаунт?"
                description={
                    <div className="w-full space-y-3">
                        <span>
                            Цю дію неможливо скасувати. Всі ваші дані, адреси, лічильники та
                            показники будуть видалені назавжди.
                        </span>
                        <Input
                            type="password"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            placeholder="Введіть пароль для підтвердження"
                            leadingIcon={<Lock className="h-4 w-4 text-neutral-500" />}
                            autoFocus
                        />
                    </div>
                }
                confirmLabel="Так, видалити акаунт"
                isLoading={deleteForm.processing}
            />
        </div>
    );
}
