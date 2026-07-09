import { Head, usePage } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import { AuthenticatedLayout } from '@/Layouts/AuthenticatedLayout';
import { useDenormalizeCollection } from '@/lib/useDenormalize';
import { formatAddressLabel } from '@/lib/formatAddress';
import { useAuthUser } from '@/lib/useAuthUser';
import type { PageProps } from '@/types';
import type { Address } from '@/types/entities';
import type { JsonApiCollectionDocument } from '@/types/jsonapi';
import { WelcomeHeader } from './Components/WelcomeHeader';
import { ConsumptionChart } from './Components/ConsumptionChart';
import { ExpenseDistribution } from './Components/ExpenseDistribution';
import { RecentReadingsTable } from './Components/RecentReadingsTable';
import { QuickActions } from './Components/QuickActions';

interface Stats {
    addressCount: number;
    meterCount: number;
    lastReadingDate: string | null;
    totalMonthlyConsumption: number;
}

interface ConsumptionDataPoint {
    month: string;
    utilityType: string;
    value: number;
}

interface ExpenseItem {
    type: string;
    displayName: string;
    amount: number;
    percentage: number;
}

interface MeterReadingData {
    id: number;
    readingValue: number;
    readingDate: string;
    consumption: number;
    meter: {
        id: number;
        name: string;
        utilityType: {
            slug: string;
            displayName: string;
            unit: string;
        };
    };
}

interface DashboardPageProps extends PageProps {
    stats: Stats;
    consumptionHistory: ConsumptionDataPoint[];
    expenseDistribution: ExpenseItem[];
    recentReadings: JsonApiCollectionDocument;
    addresses: JsonApiCollectionDocument;
}

export default function Index() {
    const { consumptionHistory, expenseDistribution, recentReadings, addresses } =
        usePage<DashboardPageProps>().props;

    const user = useAuthUser();

    const addressList = useDenormalizeCollection<Address>(addresses);

    const readingList = useDenormalizeCollection<MeterReadingData>(recentReadings);

    const [selectedAddressId, setSelectedAddressId] = useState<number | undefined>(
        addressList.find((a) => a.isPrimary)?.id ?? addressList[0]?.id,
    );

    const addressOptions = useMemo(
        () =>
            addressList.map((address) => ({
                id: address.id,
                label: formatAddressLabel(address),
                description: address.city,
            })),
        [addressList],
    );

    const userName = user?.firstName ?? user?.username ?? 'Користувач';

    return (
        <AuthenticatedLayout pageTitle="Дашборд">
            <Head title="Головна" />
            <div className="mx-auto max-w-7xl space-y-4 sm:space-y-5 lg:space-y-6">
                <WelcomeHeader
                    userName={userName}
                    addresses={addressOptions}
                    selectedAddressId={selectedAddressId}
                    onAddressChange={setSelectedAddressId}
                />

                <section className="space-y-4 sm:space-y-5 lg:space-y-6">
                    <ConsumptionChart data={consumptionHistory} />
                </section>

                <section className="grid gap-4 sm:gap-5 lg:grid-cols-2 lg:gap-6">
                    <RecentReadingsTable readings={readingList} />
                    <ExpenseDistribution data={expenseDistribution} />
                </section>

                <QuickActions />
            </div>
        </AuthenticatedLayout>
    );
}
