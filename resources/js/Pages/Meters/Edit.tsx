import { Head } from '@inertiajs/react';
import { AuthenticatedLayout } from '@/Layouts/AuthenticatedLayout';
import { MeterForm } from './Components/MeterForm';
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
    meter: { data: MeterData };
    addresses: { data: AddressItem[] };
    utilityTypes: { data: UtilityTypeItem[] };
    serviceProviders: { data: ServiceProviderItem[] };
}

export default function Edit({ meter, addresses, utilityTypes, serviceProviders }: Props) {
    return (
        <AuthenticatedLayout
            pageTitle="Редагування лічильника"
            pageSubtitle="Змініть дані лічильника та збережіть зміни"
        >
            <Head title={`Редагування: ${meter.data.name}`} />

            <MeterForm
                meter={meter.data}
                addresses={addresses.data}
                utilityTypes={utilityTypes.data}
                serviceProviders={serviceProviders.data}
            />
        </AuthenticatedLayout>
    );
}
