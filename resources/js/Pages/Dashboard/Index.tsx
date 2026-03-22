import { Head, usePage } from '@inertiajs/react';
import { useState, useMemo } from 'react';
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
    reading_value: number;
    reading_date: string;
    consumption: number;
    meter: {
        id: number;
        name: string;
        utility_type: {
            slug: string;
            display_name: string;
            unit: string;
        };
    };
}

interface Address {
    id: number;
    city: string;
    street: string;
    building_number: string;
    apartment_number: string | null;
    is_primary: boolean;
}

interface DashboardPageProps {
    stats: Stats;
    consumptionHistory: ConsumptionDataPoint[];
    expenseDistribution: ExpenseItem[];
    recentReadings: { data: MeterReadingData[] };
    addresses: { data: Address[] };
    auth: {
        user: {
            name: string;
            first_name: string | null;
            username: string;
        };
    };
}

function formatAddressLabel(address: Address): string {
    const parts = [address.street, address.building_number];
    if (address.apartment_number) {
        parts.push(`кв. ${address.apartment_number}`);
    }
    return parts.join(', ');
}

export default function Index() {
    const { stats, consumptionHistory, expenseDistribution, recentReadings, addresses, auth } =
        usePage<DashboardPageProps>().props;

    const [selectedAddressId, setSelectedAddressId] = useState<number | undefined>(
        addresses.data.find((a) => a.is_primary)?.id ?? addresses.data[0]?.id,
    );

    const addressOptions = useMemo(
        () =>
            addresses.data.map((address) => ({
                id: address.id,
                label: formatAddressLabel(address),
                description: address.city,
            })),
        [addresses.data],
    );

    const userName = auth.user.first_name ?? auth.user.username ?? 'Користувач';

    return (
        <>
            <Head title="Головна" />
            <div className="mx-auto max-w-7xl space-y-4 p-4 sm:space-y-5 sm:p-6 lg:space-y-6 lg:p-8">
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
                    <RecentReadingsTable readings={recentReadings.data} />
                    <ExpenseDistribution data={expenseDistribution} />
                </section>

                <QuickActions />
            </div>
        </>
    );
}
