import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { AuthenticatedLayout } from '@/Layouts/AuthenticatedLayout';
import { PageSectionHeader } from '@/Components/pages';
import {
    Button,
    Card,
    CardContent,
    ConfirmDialog,
    FormMessage,
    Label,
    Select,
} from '@/Components/ui';
import { MeterCard } from './Components/MeterCard';
import type { PageProps } from '@/types';

interface MeterItem {
    id: number;
    serial_number: string;
    name: string;
    description: string | null;
    location: string | null;
    is_active: boolean;
    utility_type: {
        id: number;
        display_name: string;
        unit: string;
    } | null;
    service_provider: {
        id: number;
        name: string;
    } | null;
    photo_url: string | null;
}

interface AddressItem {
    id: number;
    city: string;
    street: string;
    building_number: string;
    apartment_number: string | null;
}

interface Props extends PageProps {
    meters: { data: MeterItem[] };
    addresses: { data: AddressItem[] };
    selectedAddressId: number | null;
}

export default function Index({ meters, addresses, selectedAddressId }: Props) {
    const [deleteTarget, setDeleteTarget] = useState<MeterItem | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const addressOptions = addresses.data.map((address) => {
        const apartment = address.apartment_number ? `, кв. ${address.apartment_number}` : '';
        return {
            value: address.id,
            label: `${address.street}, ${address.building_number}${apartment}`,
        };
    });

    const handleAddressChange = (addressId: string) => {
        if (addressId) {
            router.visit(route('meters.index', { address_id: addressId }));
        } else {
            router.visit(route('meters.index'));
        }
    };

    const handleDelete = () => {
        if (!deleteTarget) {
            return;
        }
        setIsDeleting(true);
        router.delete(route('meters.destroy', deleteTarget.id), {
            onFinish: () => {
                setIsDeleting(false);
                setDeleteTarget(null);
            },
        });
    };

    return (
        <AuthenticatedLayout pageTitle="Лічильники" pageSubtitle="Переглядайте та керуйте своїми лічильниками">
            <Head title="Лічильники" />

            <div className="space-y-6">
                <Card className="border border-gray-200 shadow-lg dark:border-slate-800 dark:bg-slate-900">
                    <PageSectionHeader
                        title="Мої лічильники"
                        description="Всі лічильники, прив'язані до ваших адрес"
                        ctaButton={{
                            label: 'Додати лічильник',
                            icon: <Plus className="h-4 w-4 sm:h-5 sm:w-5" />,
                            onClick: () => router.visit(route('meters.create')),
                        }}
                    />
                    <CardContent className="space-y-5">
                        <div className="space-y-2 w-full">
                            <Label htmlFor="address-filter" className="text-sm font-semibold text-gray-600 dark:text-slate-100">
                                Фільтр за адресою
                            </Label>
                            <Select
                                id="address-filter"
                                value={selectedAddressId?.toString() ?? ''}
                                onChange={(e) => handleAddressChange(e.target.value)}
                            >
                                <option value="">Всі адреси</option>
                                {addressOptions.map((opt) => (
                                    <option key={opt.value} value={opt.value}>{opt.label}</option>
                                ))}
                            </Select>
                        </div>

                        {meters.data.length === 0 ? (
                            <div className="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-8 text-center dark:border-slate-700 dark:bg-slate-800">
                                <p className="text-gray-600 dark:text-slate-300">
                                    {selectedAddressId
                                        ? 'Для цієї адреси поки що немає збережених лічильників.'
                                        : 'У вас поки що немає лічильників.'}
                                </p>
                                <Link href={route('meters.create')} className="mt-3 inline-block">
                                    <Button type="button" size="sm">
                                        <Plus className="h-4 w-4" />
                                        Додати перший лічильник
                                    </Button>
                                </Link>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {meters.data.map((meter) => (
                                    <MeterCard
                                        key={meter.id}
                                        meter={meter}
                                        onDelete={setDeleteTarget}
                                    />
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <ConfirmDialog
                isOpen={Boolean(deleteTarget)}
                onClose={() => setDeleteTarget(null)}
                onConfirm={handleDelete}
                title="Видалити лічильник?"
                description={
                    <>
                        Ви впевнені, що хочете видалити лічильник{' '}
                        <strong>{deleteTarget?.name}</strong>
                        {deleteTarget?.serial_number ? (
                            <> (серійний №: {deleteTarget.serial_number})</>
                        ) : null}
                        ? Усі пов'язані показання також будуть видалені. Цю дію неможливо скасувати.
                    </>
                }
                confirmLabel="Видалити"
                cancelLabel="Скасувати"
                variant="danger"
                isLoading={isDeleting}
            />
        </AuthenticatedLayout>
    );
}
