import { Head } from '@inertiajs/react';
import { AuthenticatedLayout } from '@/Layouts/AuthenticatedLayout';
import { MeterForm } from './Components/MeterForm';
import type { PageProps } from '@/types';

interface AddressItem {
    id: number;
    city: string;
    street: string;
    building_number: string;
    apartment_number: string | null;
}

interface UtilityTypeItem {
    id: number;
    slug: string;
    display_name: string;
    unit: string;
}

interface ServiceProviderItem {
    id: number;
    name: string;
    address_id: number;
}

interface Props extends PageProps {
    addresses: { data: AddressItem[] };
    utilityTypes: { data: UtilityTypeItem[] };
    serviceProviders: { data: ServiceProviderItem[] };
}

export default function Create({ addresses, utilityTypes, serviceProviders }: Props) {
    return (
        <AuthenticatedLayout
            pageTitle="Додати лічильник"
            pageSubtitle="Заповніть форму, щоб додати новий лічильник до обраної адреси"
        >
            <Head title="Додати лічильник" />

            <MeterForm
                addresses={addresses.data}
                utilityTypes={utilityTypes.data}
                serviceProviders={serviceProviders.data}
            />
        </AuthenticatedLayout>
    );
}
