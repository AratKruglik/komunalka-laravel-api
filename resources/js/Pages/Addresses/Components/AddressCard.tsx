import { Link } from '@inertiajs/react'
import {
    Building2,
    EllipsisVertical,
    Home,
    BriefcaseBusiness,
    Pencil,
    Star,
    Trash2,
} from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
import { Button, DropdownMenu, type DropdownMenuItem } from '@/Components/ui'
import { formatAddressLabel } from '@/lib/formatAddress'
import type { Address } from '@/types/entities'

const ADDRESS_TYPE_ICONS: Record<string, LucideIcon> = {
    apartment: Building2,
    house: Home,
    office: BriefcaseBusiness,
}

interface AddressCardProps {
    address: Address
    onDelete: (address: Address) => void
}

export function AddressCard({ address, onDelete }: AddressCardProps) {
    const Icon = ADDRESS_TYPE_ICONS[address.addressType.icon] ?? Home
    const title = formatAddressLabel(address)
    const subtitle = [
        address.city,
        address.region.name,
        address.zipCode,
    ].filter(Boolean).join(', ')

    const actions: DropdownMenuItem[] = [
        { id: 'edit', label: 'Редагувати', icon: <Pencil className="h-4 w-4" /> },
        { id: 'delete', label: 'Видалити', icon: <Trash2 className="h-4 w-4" />, tone: 'danger' as const },
    ]

    const handleMenuSelect = (item: DropdownMenuItem) => {
        if (item.id === 'delete') onDelete(address)
    }

    const surfaceClasses = address.isPrimary
        ? 'border-2 border-primary bg-primary/10'
        : 'border border-border bg-bg-raised'

    return (
        <article
            className={`relative flex h-full flex-col gap-4 rounded-lg ${surfaceClasses} p-4 transition-shadow hover:shadow-md sm:gap-5 sm:p-5 lg:p-6`}
        >
            <div className="flex flex-wrap items-start justify-between gap-3 pr-12 sm:pr-14">
                <div className="flex flex-wrap gap-1.5 sm:gap-2">
                    <span className={`inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold ${
                        address.isPrimary
                            ? 'bg-primary text-text-inverse'
                            : 'border border-border bg-bg-raised text-text-secondary'
                    }`}>
                        <Icon className="h-3.5 w-3.5" />
                        {address.addressType.name}
                    </span>
                    {address.isPrimary ? (
                        <span className="inline-flex items-center gap-2 rounded-full bg-primary text-text-inverse px-3 py-1 text-xs font-semibold">
                            <Star className="h-3.5 w-3.5" />
                            Основна
                        </span>
                    ) : null}
                </div>

                <div className="absolute right-3 top-3">
                    <DropdownMenu
                        trigger={
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                className="h-8 w-8"
                                aria-label={`Дії для ${title}`}
                            >
                                <EllipsisVertical className="h-4 w-4" />
                            </Button>
                        }
                        items={actions}
                        onSelect={handleMenuSelect}
                    />
                </div>
            </div>

            <Link href={`/addresses/${address.id}/edit`} className="block space-y-1 sm:space-y-1.5">
                <h3 className="text-base font-bold leading-6 text-text-primary sm:text-lg sm:leading-7 lg:text-[18px]">
                    {title}
                </h3>
                <p className="text-xs text-text-muted sm:text-sm">{subtitle}</p>
            </Link>

            {address.apartmentNumber ? (
                <p className="text-xs text-text-muted">
                    кв./оф. {address.apartmentNumber}
                </p>
            ) : null}
        </article>
    )
}
