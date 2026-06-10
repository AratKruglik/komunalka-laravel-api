import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { tv } from 'tailwind-variants';
import { Eye, EyeOff } from 'lucide-react';
import { GuestLayout } from '@/Layouts/GuestLayout';

const socialButton = tv({
    base: [
        'flex w-full flex-1 items-center justify-center gap-3',
        'rounded-lg border border-neutral-200 bg-white',
        'px-4 py-2.5 text-sm font-medium text-neutral-900',
        'transition-colors hover:bg-neutral-50',
        'focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary',
        'dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700',
    ],
});

export default function Register() {
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);

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
                <div className="overflow-hidden rounded-lg bg-white shadow-lg dark:bg-slate-900">
                    <div className="px-6 py-8 sm:px-8 sm:py-10 lg:px-10 lg:py-12">
                        <div className="pb-4 text-center sm:pb-6">
                            <h1 className="text-2xl font-bold text-neutral-900 dark:text-slate-100 sm:text-3xl">
                                Створити акаунт
                            </h1>
                            <p className="mt-1 text-sm text-neutral-600 dark:text-slate-300 sm:text-base">
                                Керуйте всіма комунальними послугами в одному кабінеті
                            </p>
                        </div>

                        <div className="flex flex-col gap-3 sm:flex-row">
                            <a href="/auth/google/redirect" className={socialButton()}>
                                <svg className="h-4 w-4" viewBox="0 0 24 24">
                                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.1z" />
                                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" />
                                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" />
                                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" />
                                </svg>
                                Через Google
                            </a>

                            <a href="/auth/github/redirect" className={socialButton()}>
                                <svg className="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z" />
                                </svg>
                                Через GitHub
                            </a>
                        </div>

                        <div className="relative my-6 sm:my-8">
                            <div className="absolute inset-0 flex items-center">
                                <div className="w-full border-t border-neutral-200 dark:border-slate-700" />
                            </div>
                            <div className="relative flex justify-center">
                                <span className="bg-white px-3 text-xs font-semibold uppercase tracking-[0.25em] text-neutral-500 dark:bg-slate-900 dark:text-slate-400 sm:text-sm">
                                    Або
                                </span>
                            </div>
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-6">
                            {form.hasErrors && (
                                <div className="rounded-md bg-red-50 p-3 dark:bg-red-900/20">
                                    <p className="text-sm text-red-800 dark:text-red-300">
                                        Будь ласка, виправте помилки нижче.
                                    </p>
                                </div>
                            )}

                            <div className="space-y-5">
                                <h3 className="text-lg font-semibold text-neutral-900 dark:text-slate-100">
                                    Особиста інформація
                                </h3>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-1.5">
                                        <label
                                            htmlFor="first_name"
                                            className="block text-sm font-medium text-neutral-700 dark:text-slate-200"
                                        >
                                            Ім&apos;я <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            id="first_name"
                                            type="text"
                                            value={form.data.first_name}
                                            onChange={(e) => form.setData('first_name', e.target.value)}
                                            className="w-full rounded-md border border-neutral-300 bg-white px-3 py-2.5 text-base text-neutral-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-amber-300 dark:focus:ring-amber-300"
                                            disabled={form.processing}
                                        />
                                        {form.errors.first_name && (
                                            <p className="mt-1 text-sm text-red-500">{form.errors.first_name}</p>
                                        )}
                                    </div>

                                    <div className="space-y-1.5">
                                        <label
                                            htmlFor="last_name"
                                            className="block text-sm font-medium text-neutral-700 dark:text-slate-200"
                                        >
                                            Прізвище <span className="text-red-500">*</span>
                                        </label>
                                        <input
                                            id="last_name"
                                            type="text"
                                            value={form.data.last_name}
                                            onChange={(e) => form.setData('last_name', e.target.value)}
                                            className="w-full rounded-md border border-neutral-300 bg-white px-3 py-2.5 text-base text-neutral-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-amber-300 dark:focus:ring-amber-300"
                                            disabled={form.processing}
                                        />
                                        {form.errors.last_name && (
                                            <p className="mt-1 text-sm text-red-500">{form.errors.last_name}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="space-y-1.5">
                                    <label
                                        htmlFor="username"
                                        className="block text-sm font-medium text-neutral-700 dark:text-slate-200"
                                    >
                                        Ім&apos;я користувача <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="username"
                                        type="text"
                                        value={form.data.username}
                                        onChange={(e) => form.setData('username', e.target.value)}
                                        className="w-full rounded-md border border-neutral-300 bg-white px-3 py-2.5 text-base text-neutral-900 placeholder:text-neutral-400 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-400 dark:focus:border-amber-300 dark:focus:ring-amber-300"
                                        placeholder="my_username"
                                        disabled={form.processing}
                                    />
                                    {form.errors.username && (
                                        <p className="mt-1 text-sm text-red-500">{form.errors.username}</p>
                                    )}
                                </div>

                                <div className="space-y-1.5">
                                    <label
                                        htmlFor="email"
                                        className="block text-sm font-medium text-neutral-700 dark:text-slate-200"
                                    >
                                        Електронна пошта <span className="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="email"
                                        type="email"
                                        value={form.data.email}
                                        onChange={(e) => form.setData('email', e.target.value)}
                                        className="w-full rounded-md border border-neutral-300 bg-white px-3 py-2.5 text-base text-neutral-900 placeholder:text-neutral-400 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-400 dark:focus:border-amber-300 dark:focus:ring-amber-300"
                                        placeholder="example@mail.com"
                                        disabled={form.processing}
                                    />
                                    {form.errors.email && (
                                        <p className="mt-1 text-sm text-red-500">{form.errors.email}</p>
                                    )}
                                </div>

                                <div className="space-y-1.5">
                                    <label
                                        htmlFor="phone_number"
                                        className="block text-sm font-medium text-neutral-700 dark:text-slate-200"
                                    >
                                        Номер телефону
                                    </label>
                                    <div className="relative">
                                        <span className="absolute left-3 top-1/2 -translate-y-1/2 text-base text-neutral-500 dark:text-slate-400">
                                            +380
                                        </span>
                                        <input
                                            id="phone_number"
                                            type="tel"
                                            value={form.data.phone_number}
                                            onChange={(e) =>
                                                form.setData('phone_number', e.target.value.replace(/\D/g, ''))
                                            }
                                            className="w-full rounded-md border border-neutral-300 bg-white py-2.5 pl-14 pr-3 text-base text-neutral-900 placeholder:text-neutral-400 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-400 dark:focus:border-amber-300 dark:focus:ring-amber-300"
                                            placeholder="XX XXX XX XX"
                                            maxLength={9}
                                            disabled={form.processing}
                                        />
                                    </div>
                                    {form.errors.phone_number && (
                                        <p className="mt-1 text-sm text-red-500">{form.errors.phone_number}</p>
                                    )}
                                </div>

                                <div className="space-y-1.5">
                                    <label
                                        htmlFor="password"
                                        className="block text-sm font-medium text-neutral-700 dark:text-slate-200"
                                    >
                                        Пароль <span className="text-red-500">*</span>
                                    </label>
                                    <div className="relative">
                                        <input
                                            id="password"
                                            type={showPassword ? 'text' : 'password'}
                                            value={form.data.password}
                                            onChange={(e) => form.setData('password', e.target.value)}
                                            className="w-full rounded-md border border-neutral-300 bg-white px-3 py-2.5 pr-12 text-base text-neutral-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-amber-300 dark:focus:ring-amber-300"
                                            disabled={form.processing}
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowPassword((prev) => !prev)}
                                            className="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:text-slate-400 dark:hover:text-slate-200"
                                            aria-label={showPassword ? 'Приховати пароль' : 'Показати пароль'}
                                        >
                                            {showPassword ? (
                                                <EyeOff className="h-4 w-4" />
                                            ) : (
                                                <Eye className="h-4 w-4" />
                                            )}
                                        </button>
                                    </div>
                                    {form.errors.password && (
                                        <p className="mt-1 text-sm text-red-500">{form.errors.password}</p>
                                    )}
                                </div>

                                <div className="space-y-1.5">
                                    <label
                                        htmlFor="password_confirmation"
                                        className="block text-sm font-medium text-neutral-700 dark:text-slate-200"
                                    >
                                        Підтвердити пароль <span className="text-red-500">*</span>
                                    </label>
                                    <div className="relative">
                                        <input
                                            id="password_confirmation"
                                            type={showConfirmPassword ? 'text' : 'password'}
                                            value={form.data.password_confirmation}
                                            onChange={(e) =>
                                                form.setData('password_confirmation', e.target.value)
                                            }
                                            className="w-full rounded-md border border-neutral-300 bg-white px-3 py-2.5 pr-12 text-base text-neutral-900 focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-amber-300 dark:focus:ring-amber-300"
                                            disabled={form.processing}
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowConfirmPassword((prev) => !prev)}
                                            className="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:text-slate-400 dark:hover:text-slate-200"
                                            aria-label={
                                                showConfirmPassword
                                                    ? 'Приховати пароль'
                                                    : 'Показати пароль'
                                            }
                                        >
                                            {showConfirmPassword ? (
                                                <EyeOff className="h-4 w-4" />
                                            ) : (
                                                <Eye className="h-4 w-4" />
                                            )}
                                        </button>
                                    </div>
                                    {form.errors.password_confirmation && (
                                        <p className="mt-1 text-sm text-red-500">
                                            {form.errors.password_confirmation}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <button
                                type="submit"
                                disabled={form.processing}
                                className="w-full rounded-lg bg-primary px-4 py-3 text-lg font-bold text-gray-900 transition-colors hover:bg-primary-dark active:bg-yellow-600 disabled:cursor-not-allowed disabled:bg-gray-300"
                            >
                                {form.processing ? 'Створення акаунту...' : 'Створити акаунт'}
                            </button>

                            <div className="text-center">
                                <p className="text-sm text-neutral-600 dark:text-slate-300">
                                    Вже маєте акаунт?{' '}
                                    <Link
                                        href="/login"
                                        className="font-medium text-yellow-600 hover:text-yellow-700 hover:underline dark:text-amber-300 dark:hover:text-amber-200"
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
