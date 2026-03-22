import { useState } from 'react'
import { Head, Link, router } from '@inertiajs/react'
import { Building2, MapPin, Pencil, Plus, Trash2 } from 'lucide-react'
import { AuthenticatedLayout } from '@/Layouts/AuthenticatedLayout'
import { Button, ConfirmDialog } from '@/Components/ui'
import type { IndexPageProps, ProviderItem } from './types'

interface DeleteDialogState {
    isOpen: boolean
    provider: ProviderItem | null
}

export default function Index({ providers }: IndexPageProps) {
    const [deleteDialog, setDeleteDialog] = useState<DeleteDialogState>({
        isOpen: false,
        provider: null,
    })
    const [isDeleting, setIsDeleting] = useState(false)

    const handleDeleteClick = (provider: ProviderItem) => {
        setDeleteDialog({ isOpen: true, provider })
    }

    const handleDeleteConfirm = () => {
        if (!deleteDialog.provider) return

        setIsDeleting(true)
        router.delete(`/providers/${deleteDialog.provider.id}`, {
            onFinish: () => {
                setIsDeleting(false)
                setDeleteDialog({ isOpen: false, provider: null })
            },
        })
    }

    const handleDeleteCancel = () => {
        setDeleteDialog({ isOpen: false, provider: null })
    }

    const formatTariffLabel = (tariff: ProviderItem['tariffs'][number]): string => {
        const rate = Number(tariff.base_rate)
        const fee = Number(tariff.service_fee)
        const symbol = tariff.currency?.symbol ?? 'грн'
        const unit = tariff.utility_type?.unit ?? 'од.'
        let label = `${rate} ${symbol}/${unit}`
        if (fee > 0) {
            label += ` + ${fee} ${symbol}`
        }
        return label
    }

    return (
        <AuthenticatedLayout
            pageTitle="Мої провайдери"
            pageSubtitle="Керуйте постачальниками комунальних послуг"
        >
            <Head title="Мої провайдери" />

            <section className="w-full overflow-hidden rounded-lg bg-white shadow-lg dark:border dark:border-slate-800 dark:bg-slate-900">
                <div className="flex flex-col gap-3 px-3.5 py-5 sm:flex-row sm:items-start sm:justify-between sm:gap-4 sm:px-5 sm:py-6 lg:px-6">
                    <div>
                        <h1 className="text-xl font-bold leading-7 text-gray-800 dark:text-slate-100 sm:text-2xl sm:leading-8">
                            Мої провайдери
                        </h1>
                        <p className="mt-0.5 text-sm leading-5 text-gray-600 dark:text-slate-400 sm:mt-1 sm:text-base sm:leading-6">
                            Керуйте постачальниками комунальних послуг
                        </p>
                    </div>
                    <Link href="/providers/create">
                        <Button
                            type="button"
                            size="md"
                            className="w-full min-w-0 text-sm sm:w-auto sm:min-w-[188px] sm:text-base"
                        >
                            <Plus className="h-4 w-4 sm:h-5 sm:w-5" />
                            <span>Додати провайдера</span>
                        </Button>
                    </Link>
                </div>

                {providers.data.length > 0 ? (
                    <div className="space-y-4 px-3.5 pb-5 sm:px-5 sm:pb-6 lg:px-6">
                        {providers.data.map((provider) => (
                            <ProviderCard
                                key={provider.id}
                                provider={provider}
                                onDelete={handleDeleteClick}
                                formatTariffLabel={formatTariffLabel}
                            />
                        ))}
                    </div>
                ) : (
                    <div className="flex flex-col items-center justify-center gap-4 px-3.5 pb-8 pt-4 sm:px-5 lg:px-6">
                        <Building2 className="h-12 w-12 text-gray-300 dark:text-slate-600" />
                        <p className="text-center text-lg font-medium text-gray-600 dark:text-slate-300">
                            Провайдерів поки немає
                        </p>
                        <p className="text-center text-sm text-gray-500 dark:text-slate-400">
                            Додайте першого провайдера для однієї з ваших адрес
                        </p>
                        <Link href="/providers/create">
                            <Button type="button" size="md">
                                <Plus className="h-4 w-4" />
                                <span>Додати провайдера</span>
                            </Button>
                        </Link>
                    </div>
                )}
            </section>

            <ConfirmDialog
                isOpen={deleteDialog.isOpen}
                onClose={handleDeleteCancel}
                onConfirm={handleDeleteConfirm}
                title="Видалити провайдера?"
                description={
                    <>
                        Ви впевнені, що хочете видалити провайдера{' '}
                        <strong className="text-gray-900 dark:text-slate-50">
                            {deleteDialog.provider?.name}
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

interface ProviderCardProps {
    provider: ProviderItem
    onDelete: (provider: ProviderItem) => void
    formatTariffLabel: (tariff: ProviderItem['tariffs'][number]) => string
}

function ProviderCard({ provider, onDelete, formatTariffLabel }: ProviderCardProps) {
    return (
        <article className="space-y-3 rounded-lg border border-gray-100 p-4 dark:border-slate-700">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2">
                        <p className="font-medium text-gray-800 dark:text-slate-100">
                            {provider.name}
                        </p>
                        {provider.utility_type && (
                            <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-slate-700 dark:text-slate-300">
                                {provider.utility_type.display_name}
                            </span>
                        )}
                    </div>
                    {provider.website && (
                        <p className="text-sm text-gray-500 dark:text-slate-400">
                            <a
                                href={provider.website}
                                target="_blank"
                                rel="noreferrer"
                                className="text-yellow-700 underline hover:text-yellow-800 dark:text-yellow-400 dark:hover:text-yellow-300"
                            >
                                {provider.website.replace(/^https?:\/\//, '')}
                            </a>
                        </p>
                    )}
                </div>
                <div className="flex gap-1.5">
                    <Link href={`/providers/${provider.id}/edit`}>
                        <Button
                            type="button"
                            size="icon"
                            variant="outline"
                            tone="neutral"
                            className="h-8 w-8 text-gray-600"
                            aria-label={`Редагувати ${provider.name}`}
                        >
                            <Pencil className="h-3.5 w-3.5" />
                        </Button>
                    </Link>
                    <Button
                        type="button"
                        size="icon"
                        variant="outline"
                        tone="neutral"
                        className="h-8 w-8 text-gray-600"
                        aria-label={`Видалити ${provider.name}`}
                        onClick={() => onDelete(provider)}
                    >
                        <Trash2 className="h-3.5 w-3.5" />
                    </Button>
                </div>
            </div>

            {provider.description && (
                <p className="text-sm text-gray-600 dark:text-slate-300">{provider.description}</p>
            )}

            {provider.tariffs.length > 0 && (
                <div className="space-y-2">
                    <p className="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-slate-400">
                        Тарифи
                    </p>
                    <div className="flex flex-wrap gap-2">
                        {provider.tariffs.map((tariff) => (
                            <span
                                key={tariff.id}
                                className="inline-flex items-center gap-2 rounded-full border border-amber-100 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-900 dark:border-amber-300/30 dark:bg-amber-200/10 dark:text-amber-100"
                            >
                                <span>{tariff.name}</span>
                                <span className="text-[11px] font-medium text-amber-700 dark:text-amber-200">
                                    {formatTariffLabel(tariff)}
                                </span>
                            </span>
                        ))}
                    </div>
                </div>
            )}

            {(provider.phone || provider.email) && (
                <div className="flex flex-wrap gap-4 text-sm text-gray-500 dark:text-slate-400">
                    {provider.phone && <span>{provider.phone}</span>}
                    {provider.email && <span>{provider.email}</span>}
                </div>
            )}
        </article>
    )
}
