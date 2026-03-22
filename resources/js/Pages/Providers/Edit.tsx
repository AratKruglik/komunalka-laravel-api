import { useMemo } from 'react'
import { Head, Link } from '@inertiajs/react'
import { ChevronRight } from 'lucide-react'
import { denormalize, denormalizeCollection } from '@/lib/jsonapi'
import { AuthenticatedLayout } from '@/Layouts/AuthenticatedLayout'
import { ProviderForm } from './Components/ProviderForm'
import type { AddressItem, CurrencyItem, EditPageProps, ProviderItem, UtilityTypeItem } from './types'

const breadcrumbs = [
    { label: 'Головна', href: '/' },
    { label: 'Провайдери', href: '/providers' },
    { label: 'Редагування провайдера' },
]

export default function Edit({ provider, addresses, utilityTypes, currencies }: EditPageProps) {
    const providerData = useMemo(() => denormalize<ProviderItem>(provider), [provider])
    const addressesList = useMemo(() => denormalizeCollection<AddressItem>(addresses), [addresses])
    const utilityTypesList = useMemo(() => denormalizeCollection<UtilityTypeItem>(utilityTypes), [utilityTypes])
    const currenciesList = useMemo(() => denormalizeCollection<CurrencyItem>(currencies), [currencies])

    return (
        <AuthenticatedLayout
            pageTitle="Редагування провайдера"
            pageSubtitle="Змініть дані провайдера та збережіть зміни"
        >
            <Head title="Редагування провайдера" />

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
                    provider={providerData}
                    submitUrl={`/providers/${providerData.id}`}
                    submitMethod="put"
                    title="Редагування провайдера"
                    description="Змініть дані провайдера та натисніть «Зберегти»"
                    submitLabel="Зберегти зміни"
                />
            </div>
        </AuthenticatedLayout>
    )
}
