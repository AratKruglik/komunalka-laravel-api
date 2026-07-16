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
import { Download, Lock, User as UserIcon } from 'lucide-react';

const dangerZone = tv({
    base: [
        'rounded-xl border-2 border-error/30 bg-error/10 p-4',
        'sm:p-5 lg:p-6',
    ],
});

interface AccountTabProps {
    hasPassword: boolean;
}

export function AccountTab({ hasPassword }: AccountTabProps) {
    const [isConfirmOpen, setIsConfirmOpen] = useState(false);

    const deleteForm = useForm({
        password: '',
        confirmation: '',
    });

    const confirmField = hasPassword ? 'password' : 'confirmation';

    const handleConfirmDelete = () => {
        deleteForm.delete(route('settings.account'), {
            onFinish: () => {
                setIsConfirmOpen(false);
                deleteForm.setData(confirmField, '');
            },
        });
    };

    const handleOpenConfirm = () => {
        deleteForm.setData(confirmField, '');
        setIsConfirmOpen(true);
    };

    const handleCloseConfirm = () => {
        setIsConfirmOpen(false);
        deleteForm.setData(confirmField, '');
        deleteForm.clearErrors();
    };

    return (
        <div className="space-y-6">
            <Card className="shadow-lg">
                <CardHeader>
                    <CardTitle>Експорт даних</CardTitle>
                    <CardDescription>Завантажте копію ваших даних</CardDescription>
                </CardHeader>
                <CardContent>
                    <Button variant="secondary">
                        <Download className="h-4 w-4" />
                        Експортувати дані (CSV)
                    </Button>
                </CardContent>
            </Card>

            <div className={dangerZone()}>
                <h3 className="text-lg font-semibold text-error">
                    Небезпечна зона
                </h3>
                <p className="mt-1 text-sm text-error/80">
                    Видалення акаунта призведе до безповоротної втрати всіх ваших даних, включаючи
                    адреси, лічильники та показники.
                </p>

                {deleteForm.errors[confirmField] ? (
                    <div className="mt-4">
                        <FormMessage variant="error">{deleteForm.errors[confirmField]}</FormMessage>
                    </div>
                ) : null}

                <Button
                    variant="danger"
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
                        {hasPassword ? (
                            <Input
                                type="password"
                                value={deleteForm.data.password}
                                onChange={(e) => deleteForm.setData('password', e.target.value)}
                                placeholder="Введіть пароль для підтвердження"
                                leadingIcon={<Lock className="h-4 w-4 text-muted" />}
                                autoFocus
                            />
                        ) : (
                            <div className="space-y-1">
                                <Input
                                    type="text"
                                    value={deleteForm.data.confirmation}
                                    onChange={(e) => deleteForm.setData('confirmation', e.target.value)}
                                    placeholder="Введіть ваш email або ім'я користувача"
                                    leadingIcon={<UserIcon className="h-4 w-4 text-muted" />}
                                    autoFocus
                                />
                                <p className="text-xs text-muted">
                                    Ви увійшли через сторонній сервіс, тому для підтвердження введіть
                                    ваш email або ім&apos;я користувача.
                                </p>
                            </div>
                        )}
                    </div>
                }
                confirmLabel="Так, видалити акаунт"
                isLoading={deleteForm.processing}
            />
        </div>
    );
}
