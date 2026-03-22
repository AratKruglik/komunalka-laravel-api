import { Head } from '@inertiajs/react'
import { AuthenticatedLayout } from '@/Layouts/AuthenticatedLayout'
import { Breadcrumbs } from '@/Components/navigation/Breadcrumbs'
import { useDenormalize, useDenormalizeCollection } from '@/lib/useDenormalize'
import { AddressForm } from './Components/AddressForm'
import type { EditPageProps } from './types'
import type { Address, Region, AddressType } from '@/types/entities'

const breadcrumbs = [
    { label: 'Головна', href: '/' },
    { label: 'Мої адреси', href: '/addresses' },
    { label: 'Редагування адреси' },
]

export default function Edit({ address, regions, addressTypes }: EditPageProps) {
    const addressData = useDenormalize<Address>(address)
    const regionList = useDenormalizeCollection<Region>(regions)
    const addressTypeList = useDenormalizeCollection<AddressType>(addressTypes)

    return (
        <AuthenticatedLayout
            pageTitle="Редагування адреси"
            pageSubtitle="Змініть дані адреси та збережіть зміни"
        >
            <Head title="Редагування адреси" />

            <div className="space-y-6">
                <Breadcrumbs items={breadcrumbs} />

                <AddressForm
                    regions={regionList}
                    addressTypes={addressTypeList}
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
