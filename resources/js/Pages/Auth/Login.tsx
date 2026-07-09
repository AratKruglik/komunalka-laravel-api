import { Head, Link, useForm } from '@inertiajs/react';
import { tv } from 'tailwind-variants';
import { Mail } from 'lucide-react';
import { GuestLayout } from '@/Layouts/GuestLayout';
import { Button, Checkbox, GithubIcon, GoogleIcon, Input, Label, PasswordInput } from '@/Components/ui';

const socialButton = tv({
    base: [
        'flex items-center justify-center',
        'rounded-md border border-border bg-bg-raised',
        'py-2.5 transition-colors hover:bg-bg-surface',
    ],
});

export default function Login() {
    const form = useForm({
        email: '',
        password: '',
        remember: false,
    });

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        form.post('/login');
    }

    return (
        <GuestLayout>
            <Head title="Вхід" />

            <div className="w-full max-w-[448px]">
                <div className="overflow-hidden rounded-lg bg-bg-raised shadow-lg">
                    <div className="bg-bg-raised px-6 pt-6 pb-7">
                        <h1 className="text-center text-xl font-bold text-text-primary">
                            Комуналка
                        </h1>
                        <p className="text-center text-sm text-text-secondary">
                            Управління комунальними послугами
                        </p>
                    </div>

                    <div className="flex border-b border-border">
                        <span className="flex-1 border-b-2 border-primary px-4 py-3 text-center text-base font-medium text-text-primary">
                            Вхід
                        </span>
                        <Link
                            href="/register"
                            className="flex-1 border-b-2 border-transparent px-4 py-3 text-center text-base font-medium text-text-muted transition-colors hover:text-text-secondary"
                        >
                            Реєстрація
                        </Link>
                    </div>

                    <div className="px-4 py-5 sm:px-6 sm:py-6 lg:px-8">
                        <form onSubmit={handleSubmit} className="space-y-4 sm:space-y-5">
                            {form.errors.email && !form.errors.password && (
                                <div className="rounded-md bg-error/10 p-3">
                                    <p className="text-sm text-error">
                                        {form.errors.email}
                                    </p>
                                </div>
                            )}

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
                                    disabled={form.processing}
                                    leadingIcon={<Mail className="h-4 w-4" />}
                                    isInvalid={Boolean(form.errors.email) && Boolean(form.errors.password)}
                                />
                                {form.errors.email && form.errors.password && (
                                    <p className="mt-1 text-sm text-error">{form.errors.email}</p>
                                )}
                            </div>

                            <PasswordInput
                                id="password"
                                label="Пароль"
                                value={form.data.password}
                                onChange={(value) => form.setData('password', value)}
                                error={form.errors.password}
                                disabled={form.processing}
                                autoComplete="current-password"
                                showStrength={false}
                            />

                            <div className="flex items-center">
                                <Checkbox
                                    id="remember"
                                    checked={form.data.remember}
                                    onChange={(e) => form.setData('remember', e.target.checked)}
                                    disabled={form.processing}
                                />
                                <Label
                                    htmlFor="remember"
                                    className="ml-2 select-none font-normal"
                                >
                                    Запам'ятати мене
                                </Label>
                            </div>

                            <Button type="submit" fullWidth loading={form.processing} loadingText="Вхід...">
                                Увійти
                            </Button>
                        </form>

                        <div className="relative my-4 sm:my-5">
                            <div className="absolute inset-0 flex items-center">
                                <div className="w-full border-t border-border" />
                            </div>
                            <div className="relative flex justify-center text-xs sm:text-sm">
                                <span className="bg-bg-raised px-2 text-text-muted">
                                    Увійти через соцмережі
                                </span>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-2 sm:gap-3">
                            <a
                                href="/auth/google/redirect"
                                className={socialButton()}
                                aria-label="Увійти через Google"
                            >
                                <GoogleIcon size={16} />
                            </a>

                            <a
                                href="/auth/github/redirect"
                                className={socialButton()}
                                aria-label="Увійти через GitHub"
                            >
                                <GithubIcon size={16} className="text-text-primary" />
                            </a>
                        </div>
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
