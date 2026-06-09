import { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Lock, Eye, EyeOff } from 'lucide-react';
import { GuestLayout } from '@/Layouts/GuestLayout';

export default function ResetPassword({
    token,
    email,
}: {
    token: string;
    email: string;
}) {
    const [showPassword, setShowPassword] = useState(false);

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
                <div className="overflow-hidden rounded-lg bg-white shadow-lg dark:bg-slate-900">
                    <div className="bg-white px-6 pt-6 pb-7 dark:bg-slate-900">
                        <h1 className="text-center text-xl font-bold text-gray-900 dark:text-slate-100">
                            Комуналка
                        </h1>
                        <p className="text-center text-sm text-gray-600 dark:text-slate-300">
                            Встановлення нового пароля
                        </p>
                    </div>

                    <div className="px-4 py-5 sm:px-6 sm:py-6 lg:px-8">
                        <form onSubmit={handleSubmit} className="space-y-4 sm:space-y-5">
                            <input type="hidden" name="token" value={form.data.token} />

                            <div>
                                <label
                                    htmlFor="email"
                                    className="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-200"
                                >
                                    Електронна пошта
                                </label>
                                <input
                                    id="email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(e) => form.setData('email', e.target.value)}
                                    className="w-full rounded-md border border-gray-300 bg-gray-100 py-2.5 px-4 text-base text-gray-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400"
                                    placeholder="ваша@пошта.com"
                                    autoComplete="email"
                                    required
                                    readOnly
                                />
                                {form.errors.email && (
                                    <p className="mt-1 text-sm text-red-500">{form.errors.email}</p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="password"
                                    className="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-200"
                                >
                                    Новий пароль
                                </label>
                                <div className="relative">
                                    <input
                                        id="password"
                                        type={showPassword ? 'text' : 'password'}
                                        value={form.data.password}
                                        onChange={(e) => form.setData('password', e.target.value)}
                                        className="w-full rounded-md border border-gray-300 bg-white py-2.5 px-4 pr-12 text-base text-gray-900 placeholder:text-gray-400 transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-400 dark:focus:border-amber-300 dark:focus:ring-amber-300"
                                        placeholder="••••••••"
                                        autoComplete="new-password"
                                        required
                                        autoFocus
                                        disabled={form.processing}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword((prev) => !prev)}
                                        className="absolute inset-y-0 right-0 flex items-center pr-4 text-gray-400 transition-colors hover:text-gray-600 dark:text-slate-400 dark:hover:text-slate-200"
                                        aria-label={showPassword ? 'Приховати пароль' : 'Показати пароль'}
                                        disabled={form.processing}
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

                            <div>
                                <label
                                    htmlFor="password_confirmation"
                                    className="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-200"
                                >
                                    Підтвердження пароля
                                </label>
                                <input
                                    id="password_confirmation"
                                    type="password"
                                    value={form.data.password_confirmation}
                                    onChange={(e) => form.setData('password_confirmation', e.target.value)}
                                    className="w-full rounded-md border border-gray-300 bg-white py-2.5 px-4 text-base text-gray-900 placeholder:text-gray-400 transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-400 dark:focus:border-amber-300 dark:focus:ring-amber-300"
                                    placeholder="••••••••"
                                    autoComplete="new-password"
                                    required
                                    disabled={form.processing}
                                />
                                {form.errors.password_confirmation && (
                                    <p className="mt-1 text-sm text-red-500">
                                        {form.errors.password_confirmation}
                                    </p>
                                )}
                            </div>

                            <button
                                type="submit"
                                disabled={form.processing}
                                className="w-full rounded-md bg-primary px-4 py-2.5 text-base font-medium text-gray-900 transition-colors hover:bg-primary-dark active:bg-yellow-600 disabled:cursor-not-allowed disabled:bg-gray-300"
                            >
                                {form.processing ? 'Збереження...' : 'Скинути пароль'}
                            </button>
                        </form>
                    </div>

                    <div className="bg-gray-50 px-6 py-4 text-center dark:bg-slate-800">
                        <p className="text-xs text-gray-500 dark:text-slate-400">
                            &copy; 2023 Комуналка. Всі права захищені.
                        </p>
                    </div>
                </div>
            </div>
        </GuestLayout>
    );
}
