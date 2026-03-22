import { Head } from '@inertiajs/react'
import { AuthenticatedLayout } from '@/Layouts/AuthenticatedLayout'
import { Breadcrumbs } from '@/Components/navigation/Breadcrumbs'
import { useDenormalizeCollection } from '@/lib/useDenormalize'
import { AddressForm } from './Components/AddressForm'
import type { CreatePageProps } from './types'
import type { Region, AddressType } from '@/types/entities'

const breadcrumbs = [
    { label: 'Головна', href: '/' },
    { label: 'Мої адреси', href: '/addresses' },
    { label: 'Додати адресу' },
]

export default function Create({ regions, addressTypes }: CreatePageProps) {
    const regionList = useDenormalizeCollection<Region>(regions)
    const addressTypeList = useDenormalizeCollection<AddressType>(addressTypes)

    return (
        <AuthenticatedLayout
            pageTitle="Додати адресу"
            pageSubtitle="Створіть нову адресу для обліку комунальних послуг"
        >
            <Head title="Додати адресу" />

            <div className="space-y-6">
                <Breadcrumbs items={breadcrumbs} />

                <AddressForm
                    regions={regionList}
                    addressTypes={addressTypeList}
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
