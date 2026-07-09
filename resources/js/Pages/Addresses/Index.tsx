import { useState } from 'react'
import { Head, Link, router } from '@inertiajs/react'
import { Plus } from 'lucide-react'
import { AuthenticatedLayout } from '@/Layouts/AuthenticatedLayout'
import { Button, ConfirmDialog } from '@/Components/ui'
import { useDenormalizeCollection } from '@/lib/useDenormalize'
import { formatAddressLabel } from '@/lib/formatAddress'
import { AddressCard } from './Components/AddressCard'
import type { IndexPageProps } from './types'
import type { Address } from '@/types/entities'

interface DeleteDialogState {
    isOpen: boolean
    addressId: number | null
    addressTitle: string
}

export default function Index({ addresses }: IndexPageProps) {
    const addressList = useDenormalizeCollection<Address>(addresses)

    const [deleteDialog, setDeleteDialog] = useState<DeleteDialogState>({
        isOpen: false,
        addressId: null,
        addressTitle: '',
    })
    const [isDeleting, setIsDeleting] = useState(false)

    const handleDeleteClick = (address: Address) => {
        setDeleteDialog({
            isOpen: true,
            addressId: address.id,
            addressTitle: formatAddressLabel(address),
        })
    }

    const handleDeleteConfirm = () => {
        if (!deleteDialog.addressId) return

        setIsDeleting(true)
        router.delete(`/addresses/${deleteDialog.addressId}`, {
            onFinish: () => {
                setIsDeleting(false)
                setDeleteDialog({ isOpen: false, addressId: null, addressTitle: '' })
            },
        })
    }

    const handleDeleteCancel = () => {
        setDeleteDialog({ isOpen: false, addressId: null, addressTitle: '' })
    }

    return (
        <AuthenticatedLayout
            pageTitle="Мої адреси"
            pageSubtitle="Керуйте адресами для комунальних послуг"
        >
            <Head title="Мої адреси" />

            <section className="w-full overflow-hidden rounded-lg bg-bg-raised shadow-lg border border-border">
                <div className="flex flex-col gap-3 px-3.5 py-5 sm:flex-row sm:items-start sm:justify-between sm:gap-4 sm:px-5 sm:py-6 lg:px-6">
                    <div>
                        <h1 className="text-xl font-bold leading-7 text-text-primary sm:text-2xl sm:leading-8 lg:text-[24px] lg:leading-[32px]">
                            Мої адреси
                        </h1>
                        <p className="mt-0.5 text-sm leading-5 text-text-secondary sm:mt-1 sm:text-base sm:leading-6 lg:text-[16px] lg:leading-[24px]">
                            Керуйте адресами для комунальних послуг
                        </p>
                    </div>
                    <Link href="/addresses/create">
                        <Button
                            type="button"
                            size="md"
                            className="w-full min-w-0 text-sm sm:w-auto sm:min-w-[166px] sm:text-base"
                        >
                            <Plus className="h-4 w-4 sm:h-5 sm:w-5" />
                            <span>Додати адресу</span>
                        </Button>
                    </Link>
                </div>

                {addressList.length > 0 ? (
                    <div className="grid gap-4 px-3.5 pb-5 sm:gap-5 sm:px-5 sm:pb-6 md:grid-cols-2 lg:gap-6 lg:px-6 xl:grid-cols-3 2xl:grid-cols-4">
                        {addressList.map((address) => (
                            <AddressCard
                                key={address.id}
                                address={address}
                                onDelete={handleDeleteClick}
                            />
                        ))}
                    </div>
                ) : (
                    <div className="flex flex-col items-center justify-center gap-4 px-3.5 pb-8 pt-4 sm:px-5 lg:px-6">
                        <p className="text-center text-sm text-text-muted">
                            У вас ще немає збережених адрес
                        </p>
                        <Link href="/addresses/create">
                            <Button type="button" size="md">
                                <Plus className="h-4 w-4" />
                                <span>Додати першу адресу</span>
                            </Button>
                        </Link>
                    </div>
                )}
            </section>

            <ConfirmDialog
                isOpen={deleteDialog.isOpen}
                onClose={handleDeleteCancel}
                onConfirm={handleDeleteConfirm}
                title="Видалити адресу?"
                description={
                    <>
                        Ви впевнені, що хочете видалити адресу{' '}
                        <strong className="text-text-primary">
                            {deleteDialog.addressTitle}
                        </strong>
                        ? Цю дію неможливо скасувати.
                    </>
                }
                confirmLabel="Видалити"
                cancelLabel="Скасувати"
                variant="danger"
                isLoading={isDeleting}
            />
        </AuthenticatedLayout>
    )
}
