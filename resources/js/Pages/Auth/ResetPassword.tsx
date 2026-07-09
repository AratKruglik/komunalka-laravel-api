import { Head, useForm } from '@inertiajs/react';
import { GuestLayout } from '@/Layouts/GuestLayout';
import { Button, Input, Label, PasswordInput } from '@/Components/ui';

export default function ResetPassword({
    token,
    email,
}: {
    token: string;
    email: string;
}) {
    const form = useForm({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        form.post('/reset-password');
    }

    return (
        <GuestLayout>
            <Head title="Скидання пароля" />

            <div className="w-full max-w-[448px]">
                <div className="overflow-hidden rounded-lg bg-bg-raised shadow-lg">
                    <div className="bg-bg-raised px-6 pt-6 pb-7">
                        <h1 className="text-center text-xl font-bold text-text-primary">
                            Комуналка
                        </h1>
                        <p className="text-center text-sm text-text-secondary">
                            Встановлення нового пароля
                        </p>
                    </div>

                    <div className="px-4 py-5 sm:px-6 sm:py-6 lg:px-8">
                        <form onSubmit={handleSubmit} className="space-y-4 sm:space-y-5">
                            <input type="hidden" name="token" value={form.data.token} />

                            <div>
                                <Label htmlFor="email" className="mb-1.5">
                                    Електронна пошта
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(e) => form.setData('email', e.target.value)}
                                    className="bg-bg-surface text-text-muted"
                                    placeholder="ваша@пошта.com"
                                    autoComplete="email"
                                    required
                                    readOnly
                                />
                                {form.errors.email && (
                                    <p className="mt-1 text-sm text-error">{form.errors.email}</p>
                                )}
                            </div>

                            <PasswordInput
                                id="password"
                                label="Новий пароль"
                                value={form.data.password}
                                onChange={(value) => form.setData('password', value)}
                                error={form.errors.password}
                                disabled={form.processing}
                                autoComplete="new-password"
                                autoFocus
                                showStrength={false}
                            />

                            <PasswordInput
                                id="password_confirmation"
                                label="Підтвердження пароля"
                                value={form.data.password_confirmation}
                                onChange={(value) => form.setData('password_confirmation', value)}
                                error={form.errors.password_confirmation}
                                disabled={form.processing}
                                autoComplete="new-password"
                                showStrength={false}
                            />

                            <Button type="submit" fullWidth loading={form.processing} loadingText="Збереження...">
                                Скинути пароль
                            </Button>
                        </form>
                    </div>

                    <div className="bg-bg-surface px-6 py-4 text-center">
                        <p className="text-xs text-text-muted">
                            &copy; 2023 Комуналка. Всі права захищені.
                        </p>
                    </div>
                </div>
            </div>
        </GuestLayout>
    );
}
