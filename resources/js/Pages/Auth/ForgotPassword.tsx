import { Head, Link, useForm } from '@inertiajs/react';
import { Mail, ArrowLeft } from 'lucide-react';
import { GuestLayout } from '@/Layouts/GuestLayout';
import { Button, Input, Label } from '@/Components/ui';

export default function ForgotPassword({ status }: { status?: string }) {
    const form = useForm({
        email: '',
    });

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        form.post('/forgot-password');
    }

    return (
        <GuestLayout>
            <Head title="Забули пароль" />

            <div className="w-full max-w-[448px]">
                <div className="overflow-hidden rounded-lg bg-raised shadow-lg">
                    <div className="bg-raised px-6 pt-6 pb-7">
                        <h1 className="text-center text-xl font-bold text-foreground">
                            Комуналка
                        </h1>
                        <p className="text-center text-sm text-subtext">
                            Відновлення доступу до акаунту
                        </p>
                    </div>

                    <div className="px-4 py-5 sm:px-6 sm:py-6 lg:px-8">
                        {status && (
                            <div className="mb-4 rounded-md bg-success/10 p-3">
                                <p className="text-sm font-medium text-success">
                                    {status}
                                </p>
                            </div>
                        )}

                        <div className="mb-6 text-sm text-subtext">
                            Забули пароль? Без проблем. Просто вкажіть вашу адресу електронної пошти,
                            і ми надішлемо вам посилання для встановлення нового пароля.
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-4 sm:space-y-5">
                            <div>
                                <Label htmlFor="email" className="mb-1.5">
                                    Електронна пошта
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(e) => form.setData('email', e.target.value)}
                                    placeholder="ваша@пошта.com"
                                    autoComplete="email"
                                    required
                                    autoFocus
                                    disabled={form.processing}
                                    leadingIcon={<Mail className="h-4 w-4" />}
                                    isInvalid={Boolean(form.errors.email)}
                                />
                                {form.errors.email && (
                                    <p className="mt-1 text-sm text-error">{form.errors.email}</p>
                                )}
                            </div>

                            <Button type="submit" fullWidth loading={form.processing} loadingText="Відправка...">
                                Надіслати посилання
                            </Button>
                        </form>

                        <div className="mt-6 text-center">
                            <Link
                                href="/login"
                                className="inline-flex items-center text-sm font-medium text-primary-dark hover:text-primary"
                            >
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Повернутися до входу
                            </Link>
                        </div>
                    </div>

                    <div className="bg-surface px-6 py-4 text-center">
                        <p className="text-xs text-muted">
                            &copy; 2023 Комуналка. Всі права захищені.
                        </p>
                    </div>
                </div>
            </div>
        </GuestLayout>
    );
}
