import { Head } from '@inertiajs/react'
import { useDenormalize, useDenormalizeCollection } from '@/lib/useDenormalize'
import { AuthenticatedLayout } from '@/Layouts/AuthenticatedLayout'
import { Breadcrumbs } from '@/Components/navigation/Breadcrumbs'
import { ProviderForm } from './Components/ProviderForm'
import type { CurrencyItem, EditPageProps, ProviderAddressItem, ProviderItem, UtilityTypeItem } from './types'

const breadcrumbs = [
    { label: 'Головна', href: '/' },
    { label: 'Провайдери', href: '/providers' },
    { label: 'Редагування провайдера' },
]

export default function Edit({ provider, addresses, utilityTypes, currencies }: EditPageProps) {
    const providerData = useDenormalize<ProviderItem>(provider)
    const addressesList = useDenormalizeCollection<ProviderAddressItem>(addresses)
    const utilityTypesList = useDenormalizeCollection<UtilityTypeItem>(utilityTypes)
    const currenciesList = useDenormalizeCollection<CurrencyItem>(currencies)

    return (
        <AuthenticatedLayout
            pageTitle="Редагування провайдера"
            pageSubtitle="Змініть дані провайдера та збережіть зміни"
        >
            <Head title="Редагування провайдера" />

            <div className="space-y-6">
                <Breadcrumbs items={breadcrumbs} />

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
