import { Head } from '@inertiajs/react';
import { AuthenticatedLayout } from '@/Layouts/AuthenticatedLayout';
import { MeterForm } from './Components/MeterForm';
import { useDenormalizeCollection } from '@/lib/useDenormalize';
import type { JsonApiCollectionDocument } from '@/types/jsonapi';
import type { PageProps } from '@/types';
import type { Address } from '@/types/entities';

interface UtilityTypeItem {
    id: number;
    slug: string;
    displayName: string;
    unit: string;
}

interface ServiceProviderItem {
    id: number;
    name: string;
    addressId: number;
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
    const addresses = useDenormalizeCollection<Address>(addressesDocument);
    const utilityTypes = useDenormalizeCollection<UtilityTypeItem>(utilityTypesDocument);
    const serviceProviders = useDenormalizeCollection<ServiceProviderItem>(serviceProvidersDocument);

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
