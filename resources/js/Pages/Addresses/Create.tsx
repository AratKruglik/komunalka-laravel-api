import { Head, Link } from '@inertiajs/react'
import { ChevronRight } from 'lucide-react'
import { AuthenticatedLayout } from '@/Layouts/AuthenticatedLayout'
import { AddressForm } from './Components/AddressForm'
import type { CreatePageProps } from './types'

const breadcrumbs = [
    { label: 'Головна', href: '/' },
    { label: 'Мої адреси', href: '/addresses' },
    { label: 'Додати адресу' },
]

export default function Create({ regions, addressTypes }: CreatePageProps) {
    return (
        <AuthenticatedLayout
            pageTitle="Додати адресу"
            pageSubtitle="Створіть нову адресу для обліку комунальних послуг"
        >
            <Head title="Додати адресу" />

            <div className="space-y-6">
                <nav
                    aria-label="Breadcrumb"
                    className="flex flex-wrap items-center gap-2 text-sm text-gray-500 dark:text-slate-400"
                >
                    {breadcrumbs.map((breadcrumb, index) => {
                        const isLast = index === breadcrumbs.length - 1

                        return (
                            <span key={breadcrumb.label} className="flex items-center gap-2">
                                {breadcrumb.href && !isLast ? (
                                    <Link
                                        href={breadcrumb.href}
                                        className="transition-colors hover:text-gray-700 dark:hover:text-slate-200"
                                    >
                                        {breadcrumb.label}
                                    </Link>
                                ) : (
                                    <span
                                        className={isLast ? 'font-medium text-gray-700 dark:text-slate-200' : undefined}
                                    >
                                        {breadcrumb.label}
                                    </span>
                                )}
                                {!isLast ? <ChevronRight className="h-4 w-4" /> : null}
                            </span>
                        )
                    })}
                </nav>

                <AddressForm
                    regions={regions.data}
                    addressTypes={addressTypes.data}
                    submitUrl="/addresses"
                    submitMethod="post"
                    title="Додати нову адресу"
                    description="Заповніть форму нижче, щоб додати нову адресу для обліку комунальних послуг"
                    submitLabel="Зберегти адресу"
                />
            </div>
        </AuthenticatedLayout>
    )
}
