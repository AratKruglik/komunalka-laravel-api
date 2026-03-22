import { useMemo } from 'react';
import { Head } from '@inertiajs/react';
import { AuthenticatedLayout } from '@/Layouts/AuthenticatedLayout';
import { MeterForm } from './Components/MeterForm';
import { denormalizeCollection } from '@/lib/jsonapi';
import type { JsonApiCollectionDocument } from '@/types/jsonapi';
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
    addresses: JsonApiCollectionDocument;
    utilityTypes: JsonApiCollectionDocument;
    serviceProviders: JsonApiCollectionDocument;
}

export default function Create({
    addresses: addressesDocument,
    utilityTypes: utilityTypesDocument,
    serviceProviders: serviceProvidersDocument,
}: Props) {
    const addresses = useMemo(() => denormalizeCollection<AddressItem>(addressesDocument), [addressesDocument]);
    const utilityTypes = useMemo(() => denormalizeCollection<UtilityTypeItem>(utilityTypesDocument), [utilityTypesDocument]);
    const serviceProviders = useMemo(() => denormalizeCollection<ServiceProviderItem>(serviceProvidersDocument), [serviceProvidersDocument]);

    return (
        <AuthenticatedLayout
            pageTitle="Додати лічильник"
            pageSubtitle="Заповніть форму, щоб додати новий лічильник до обраної адреси"
        >
            <Head title="Додати лічильник" />

            <MeterForm
                addresses={addresses}
                utilityTypes={utilityTypes}
                serviceProviders={serviceProviders}
            />
        </AuthenticatedLayout>
    );
}
