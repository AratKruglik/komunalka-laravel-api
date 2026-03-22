import { useMemo } from 'react'
import { Head, Link } from '@inertiajs/react'
import { ChevronRight } from 'lucide-react'
import { denormalizeCollection } from '@/lib/jsonapi'
import { AuthenticatedLayout } from '@/Layouts/AuthenticatedLayout'
import { ProviderForm } from './Components/ProviderForm'
import type { AddressItem, CreatePageProps, CurrencyItem, UtilityTypeItem } from './types'

const breadcrumbs = [
    { label: 'Головна', href: '/' },
    { label: 'Провайдери', href: '/providers' },
    { label: 'Додати провайдера' },
]

export default function Create({ addresses, utilityTypes, currencies }: CreatePageProps) {
    const addressesList = useMemo(() => denormalizeCollection<AddressItem>(addresses), [addresses])
    const utilityTypesList = useMemo(() => denormalizeCollection<UtilityTypeItem>(utilityTypes), [utilityTypes])
    const currenciesList = useMemo(() => denormalizeCollection<CurrencyItem>(currencies), [currencies])

    return (
        <AuthenticatedLayout
            pageTitle="Додати провайдера"
            pageSubtitle="Створіть запис про постачальника комунальних послуг"
        >
            <Head title="Додати провайдера" />

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

                <ProviderForm
                    addresses={addressesList}
                    utilityTypes={utilityTypesList}
                    currencies={currenciesList}
                    submitUrl="/providers"
                    submitMethod="post"
                    title="Новий провайдер"
                    description="Додайте постачальника комунальних послуг для однієї з ваших адрес"
                    submitLabel="Зберегти провайдера"
                />
            </div>
        </AuthenticatedLayout>
    )
}
