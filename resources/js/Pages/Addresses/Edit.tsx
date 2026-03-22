import { Head, Link } from '@inertiajs/react'
import { ChevronRight } from 'lucide-react'
import { AuthenticatedLayout } from '@/Layouts/AuthenticatedLayout'
import { AddressForm } from './Components/AddressForm'
import type { EditPageProps } from './types'

const breadcrumbs = [
    { label: 'Головна', href: '/' },
    { label: 'Мої адреси', href: '/addresses' },
    { label: 'Редагування адреси' },
]

export default function Edit({ address, regions, addressTypes }: EditPageProps) {
    const addressData = address.data

    return (
        <AuthenticatedLayout
            pageTitle="Редагування адреси"
            pageSubtitle="Змініть дані адреси та збережіть зміни"
        >
            <Head title="Редагування адреси" />

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
                    address={addressData}
                    submitUrl={`/addresses/${addressData.id}`}
                    submitMethod="put"
                    title="Редагування адреси"
                    description="Змініть дані адреси та натисніть «Зберегти»"
                    submitLabel="Зберегти зміни"
                />
            </div>
        </AuthenticatedLayout>
    )
}
