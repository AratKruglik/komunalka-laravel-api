import { Head, Link, useForm } from '@inertiajs/react';
import { Mail, ArrowLeft } from 'lucide-react';
import { GuestLayout } from '@/Layouts/GuestLayout';

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
                <div className="overflow-hidden rounded-lg bg-white shadow-lg dark:bg-slate-900">
                    <div className="bg-white px-6 pt-6 pb-7 dark:bg-slate-900">
                        <h1 className="text-center text-xl font-bold text-gray-900 dark:text-slate-100">
                            Комуналка
                        </h1>
                        <p className="text-center text-sm text-gray-600 dark:text-slate-300">
                            Відновлення доступу до акаунту
                        </p>
                    </div>

                    <div className="px-4 py-5 sm:px-6 sm:py-6 lg:px-8">
                        {status && (
                            <div className="mb-4 rounded-md bg-green-50 p-3 dark:bg-green-900/20">
                                <p className="text-sm font-medium text-green-800 dark:text-green-300">
                                    {status}
                                </p>
                            </div>
                        )}

                        <div className="mb-6 text-sm text-gray-600 dark:text-slate-400">
                            Забули пароль? Без проблем. Просто вкажіть вашу адресу електронної пошти, 
                            і ми надішлемо вам посилання для встановлення нового пароля.
                        </div>

                        <form onSubmit={handleSubmit} className="space-y-4 sm:space-y-5">
                            <div>
                                <label
                                    htmlFor="email"
                                    className="mb-1.5 block text-sm font-medium text-gray-700 dark:text-slate-200"
                                >
                                    Електронна пошта
                                </label>
                                <div className="relative">
                                    <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                        <Mail className="h-4 w-4 text-gray-400" />
                                    </div>
                                    <input
                                        id="email"
                                        type="email"
                                        value={form.data.email}
                                        onChange={(e) => form.setData('email', e.target.value)}
                                        className="w-full rounded-md border border-gray-300 bg-white py-2.5 pl-11 pr-4 text-base text-gray-900 placeholder:text-gray-400 transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary disabled:cursor-not-allowed disabled:bg-gray-100 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-400 dark:focus:border-amber-300 dark:focus:ring-amber-300"
                                        placeholder="ваша@пошта.com"
                                        autoComplete="email"
                                        required
                                        autoFocus
                                        disabled={form.processing}
                                    />
                                </div>
                                {form.errors.email && (
                                    <p className="mt-1 text-sm text-red-500">{form.errors.email}</p>
                                )}
                            </div>

                            <button
                                type="submit"
                                disabled={form.processing}
                                className="w-full rounded-md bg-primary px-4 py-2.5 text-base font-medium text-gray-900 transition-colors hover:bg-primary-dark active:bg-yellow-600 disabled:cursor-not-allowed disabled:bg-gray-300"
                            >
                                {form.processing ? 'Відправка...' : 'Надіслати посилання'}
                            </button>
                        </form>

                        <div className="mt-6 text-center">
                            <Link
                                href="/login"
                                className="inline-flex items-center text-sm font-medium text-yellow-600 hover:text-yellow-700 dark:text-amber-300 dark:hover:text-amber-200"
                            >
                                <ArrowLeft className="mr-2 h-4 w-4" />
                                Повернутися до входу
                            </Link>
                        </div>
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
