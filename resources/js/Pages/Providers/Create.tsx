import { Head } from '@inertiajs/react'
import { useDenormalizeCollection } from '@/lib/useDenormalize'
import { AuthenticatedLayout } from '@/Layouts/AuthenticatedLayout'
import { Breadcrumbs } from '@/Components/navigation/Breadcrumbs'
import { ProviderForm } from './Components/ProviderForm'
import type { CreatePageProps, CurrencyItem, ProviderAddressItem, UtilityTypeItem } from './types'

const breadcrumbs = [
    { label: 'Головна', href: '/' },
    { label: 'Провайдери', href: '/providers' },
    { label: 'Додати провайдера' },
]

export default function Create({ addresses, utilityTypes, currencies }: CreatePageProps) {
    const addressesList = useDenormalizeCollection<ProviderAddressItem>(addresses)
    const utilityTypesList = useDenormalizeCollection<UtilityTypeItem>(utilityTypes)
    const currenciesList = useDenormalizeCollection<CurrencyItem>(currencies)

    return (
        <AuthenticatedLayout
            pageTitle="Додати провайдера"
            pageSubtitle="Створіть запис про постачальника комунальних послуг"
        >
            <Head title="Додати провайдера" />

            <div className="space-y-6">
                <Breadcrumbs items={breadcrumbs} />

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
