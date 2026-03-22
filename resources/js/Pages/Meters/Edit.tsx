import { useMemo } from 'react';
import { Head } from '@inertiajs/react';
import { AuthenticatedLayout } from '@/Layouts/AuthenticatedLayout';
import { MeterForm } from './Components/MeterForm';
import { denormalize, denormalizeCollection } from '@/lib/jsonapi';
import type { JsonApiCollectionDocument, JsonApiDocument } from '@/types/jsonapi';
import type { PageProps } from '@/types';

interface UtilityTypeItem {
    id: number;
    slug: string;
    display_name: string;
    unit: string;
}

interface MeterData {
    id: number;
    address_id: number;
    serial_number: string;
    name: string;
    description: string | null;
    model_name: string | null;
    location: string | null;
    installation_date: string | null;
    initial_reading: number | null;
    notes: string | null;
    is_active: boolean;
    utility_type: UtilityTypeItem | null;
    service_provider: { id: number; name: string } | null;
    photo_url: string | null;
}

interface AddressItem {
    id: number;
    city: string;
    street: string;
    building_number: string;
    apartment_number: string | null;
}

interface ServiceProviderItem {
    id: number;
    name: string;
    address_id: number;
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
    const meter = useMemo(() => denormalize<MeterData>(meterDocument), [meterDocument]);
    const addresses = useMemo(() => denormalizeCollection<AddressItem>(addressesDocument), [addressesDocument]);
    const utilityTypes = useMemo(() => denormalizeCollection<UtilityTypeItem>(utilityTypesDocument), [utilityTypesDocument]);
    const serviceProviders = useMemo(() => denormalizeCollection<ServiceProviderItem>(serviceProvidersDocument), [serviceProvidersDocument]);

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
