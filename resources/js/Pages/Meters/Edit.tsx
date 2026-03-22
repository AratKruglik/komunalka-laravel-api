import { Head } from '@inertiajs/react';
import { AuthenticatedLayout } from '@/Layouts/AuthenticatedLayout';
import { MeterForm } from './Components/MeterForm';
import { useDenormalize, useDenormalizeCollection } from '@/lib/useDenormalize';
import type { JsonApiCollectionDocument, JsonApiDocument } from '@/types/jsonapi';
import type { PageProps } from '@/types';
import type { Address, Meter } from '@/types/entities';

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
    meter: JsonApiDocument;
    addresses: JsonApiCollectionDocument;
    utilityTypes: JsonApiCollectionDocument;
    serviceProviders: JsonApiCollectionDocument;
}

export default function Edit({
    meter: meterDocument,
    addresses: addressesDocument,
    utilityTypes: utilityTypesDocument,
    serviceProviders: serviceProvidersDocument,
}: Props) {
    const meter = useDenormalize<Meter>(meterDocument);
    const addresses = useDenormalizeCollection<Address>(addressesDocument);
    const utilityTypes = useDenormalizeCollection<UtilityTypeItem>(utilityTypesDocument);
    const serviceProviders = useDenormalizeCollection<ServiceProviderItem>(serviceProvidersDocument);

    return (
        <AuthenticatedLayout
            pageTitle="Редагування лічильника"
            pageSubtitle="Змініть дані лічильника та збережіть зміни"
        >
            <Head title={`Редагування: ${meter.name}`} />

            <MeterForm
                meter={meter}
                addresses={addresses}
                utilityTypes={utilityTypes}
                serviceProviders={serviceProviders}
            />
        </AuthenticatedLayout>
    );
}
