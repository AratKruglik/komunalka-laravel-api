import { Head, Link, useForm } from '@inertiajs/react';
import { tv } from 'tailwind-variants';
import { GuestLayout } from '@/Layouts/GuestLayout';
import { Button, GithubIcon, GoogleIcon, Input, Label, PasswordInput } from '@/Components/ui';

const socialButton = tv({
    base: [
        'flex w-full flex-1 items-center justify-center gap-3',
        'rounded-lg border border-border bg-bg-raised',
        'px-4 py-2.5 text-sm font-medium text-text-primary',
        'transition-colors hover:bg-bg-surface',
        'focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary',
    ],
});

export default function Register() {
    const form = useForm({
        username: '',
        first_name: '',
        last_name: '',
        phone_number: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    function handleSubmit(event: React.FormEvent) {
        event.preventDefault();
        form.post('/register');
    }

    return (
        <GuestLayout>
            <Head title="Реєстрація" />

            <div className="w-full max-w-4xl">
                <div className="overflow-hidden rounded-lg bg-bg-raised shadow-lg">
                    <div className="px-6 py-8 sm:px-8 sm:py-10 lg:px-10 lg:py-12">
                        <div className="pb-4 text-center sm:pb-6">
                            <h1 className="text-2xl font-bold text-text-primary sm:text-3xl">
                                Створити акаунт
                            </h1>
                            <p className="mt-1 text-sm text-text-secondary sm:text-base">
                                Керуйте всіма комунальними послугами в одному кабінеті
                            </p>
                        </div>

                        <div className="flex flex-col gap-3 sm:flex-row">
                            <a href="/auth/google/redirect" className={socialButton()}>
                                <GoogleIcon size={16} />
                                Через Google
                            </a>

                            <a href="/auth/github/redirect" className={socialButton()}>
                                <GithubIcon size={16} />
                                Через GitHub
                            </a>
                        </div>

                        <div className="relative my-6 sm:my-8">
                            <div className="absolute inset-0 flex items-center">
                                <div className="w-full border-t border-border" />
                            </div>
                            <div className="relative flex justify-center">
                                <span className="bg-bg-raised px-3 text-xs font-semibold uppercase tracking-[0.25em] text-text-muted sm:text-sm">
                                    Або
                                </span>
                            </div>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-6">
                            {form.hasErrors && (
                                <div className="rounded-md bg-error/10 p-3">
                                    <p className="text-sm text-error">
                                        Будь ласка, виправте помилки нижче.
                                    </p>
                                </div>
                            )}

                            <div className="space-y-5">
                                <h3 className="text-lg font-semibold text-text-primary">
                                    Особиста інформація
                                </h3>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-1.5">
                                        <Label htmlFor="first_name" isRequired>
                                            Ім&apos;я
                                        </Label>
                                        <Input
                                            id="first_name"
                                            type="text"
                                            value={form.data.first_name}
                                            onChange={(e) => form.setData('first_name', e.target.value)}
                                            disabled={form.processing}
                                            isInvalid={Boolean(form.errors.first_name)}
                                        />
                                        {form.errors.first_name && (
                                            <p className="mt-1 text-sm text-error">{form.errors.first_name}</p>
                                        )}
                                    </div>

                                    <div className="space-y-1.5">
                                        <Label htmlFor="last_name" isRequired>
                                            Прізвище
                                        </Label>
                                        <Input
                                            id="last_name"
                                            type="text"
                                            value={form.data.last_name}
                                            onChange={(e) => form.setData('last_name', e.target.value)}
                                            disabled={form.processing}
                                            isInvalid={Boolean(form.errors.last_name)}
                                        />
                                        {form.errors.last_name && (
                                            <p className="mt-1 text-sm text-error">{form.errors.last_name}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="username" isRequired>
                                        Ім&apos;я користувача
                                    </Label>
                                    <Input
                                        id="username"
                                        type="text"
                                        value={form.data.username}
                                        onChange={(e) => form.setData('username', e.target.value)}
                                        placeholder="my_username"
                                        disabled={form.processing}
                                        isInvalid={Boolean(form.errors.username)}
                                    />
                                    {form.errors.username && (
                                        <p className="mt-1 text-sm text-error">{form.errors.username}</p>
                                    )}
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="email" isRequired>
                                        Електронна пошта
                                    </Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={form.data.email}
                                        onChange={(e) => form.setData('email', e.target.value)}
                                        placeholder="example@mail.com"
                                        disabled={form.processing}
                                        isInvalid={Boolean(form.errors.email)}
                                    />
                                    {form.errors.email && (
                                        <p className="mt-1 text-sm text-error">{form.errors.email}</p>
                                    )}
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="phone_number">
                                        Номер телефону
                                    </Label>
                                    <Input
                                        id="phone_number"
                                        type="tel"
                                        value={form.data.phone_number}
                                        onChange={(e) =>
                                            form.setData('phone_number', e.target.value.replace(/\D/g, ''))
                                        }
                                        leadingIcon={<span className="text-base">+380</span>}
                                        placeholder="XX XXX XX XX"
                                        maxLength={9}
                                        disabled={form.processing}
                                        isInvalid={Boolean(form.errors.phone_number)}
                                    />
                                    {form.errors.phone_number && (
                                        <p className="mt-1 text-sm text-error">{form.errors.phone_number}</p>
                                    )}
                                </div>

                                <PasswordInput
                                    id="password"
                                    label={<>Пароль <span className="text-error">*</span></>}
                                    value={form.data.password}
                                    onChange={(value) => form.setData('password', value)}
                                    error={form.errors.password}
                                    disabled={form.processing}
                                    showStrength={false}
                                />

                                <PasswordInput
                                    id="password_confirmation"
                                    label={<>Підтвердити пароль <span className="text-error">*</span></>}
                                    value={form.data.password_confirmation}
                                    onChange={(value) => form.setData('password_confirmation', value)}
                                    error={form.errors.password_confirmation}
                                    disabled={form.processing}
                                    showStrength={false}
                                />
                            </div>

                            <Button type="submit" fullWidth size="lg" loading={form.processing} loadingText="Створення акаунту...">
                                Створити акаунт
                            </Button>

                            <div className="text-center">
                                <p className="text-sm text-text-secondary">
                                    Вже маєте акаунт?{' '}
                                    <Link
                                        href="/login"
                                        className="font-medium text-primary-dark hover:underline"
                                    >
                                        Увійти
                                    </Link>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </GuestLayout>
    );
}
