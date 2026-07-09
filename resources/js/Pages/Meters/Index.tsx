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
    Label,
    Select,
} from '@/Components/ui';
import { MeterCard } from './Components/MeterCard';
import { useDenormalizeCollection } from '@/lib/useDenormalize';
import { formatAddressLabel } from '@/lib/formatAddress';
import type { JsonApiCollectionDocument } from '@/types/jsonapi';
import type { PageProps } from '@/types';
import type { Meter, Address } from '@/types/entities';

interface Props extends PageProps {
    meters: JsonApiCollectionDocument;
    addresses: JsonApiCollectionDocument;
    selectedAddressId: number | null;
}

export default function Index({ meters: metersDocument, addresses: addressesDocument, selectedAddressId }: Props) {
    const meters = useDenormalizeCollection<Meter>(metersDocument);
    const addresses = useDenormalizeCollection<Address>(addressesDocument);
    const [deleteTarget, setDeleteTarget] = useState<Meter | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const addressOptions = addresses.map((address) => ({
        value: address.id,
        label: formatAddressLabel(address),
    }));

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
                <Card className="shadow-lg">
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
                            <Label htmlFor="address-filter" className="text-sm font-semibold">
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

                        {meters.length === 0 ? (
                            <div className="rounded-xl border border-dashed border-line bg-surface p-8 text-center">
                                <p className="text-subtext">
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
                                {meters.map((meter) => (
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
                        {deleteTarget?.serialNumber ? (
                            <> (серійний №: {deleteTarget.serialNumber})</>
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
