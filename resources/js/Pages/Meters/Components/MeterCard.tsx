import { MoreVertical, Pencil, Trash2 } from 'lucide-react';
import { router } from '@inertiajs/react';
import { tv } from 'tailwind-variants';
import { Button, DropdownMenu, Spinner, type DropdownMenuItem } from '@/Components/ui';
import { resolveMediaSrc } from '@/lib/media';
import type { Meter } from '@/types/entities';

interface MeterCardProps {
    meter: Meter;
    onDelete: (meter: Meter) => void;
}

const METER_ACTIONS: DropdownMenuItem[] = [
    {
        id: 'edit',
        label: 'Редагувати',
        icon: <Pencil className="h-4 w-4" />,
    },
    {
        id: 'delete',
        label: 'Видалити',
        icon: <Trash2 className="h-4 w-4" />,
        tone: 'danger',
    },
];

const statusBadge = tv({
    base: 'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
    variants: {
        active: {
            true: 'bg-success/10 text-success border border-success/30',
            false: 'bg-surface text-muted border border-line',
        },
    },
});

export function MeterCard({ meter, onDelete }: MeterCardProps) {
    const thumbnailSrc = resolveMediaSrc(meter.photo, 'optimized_url');

    const handleMenuSelect = (item: DropdownMenuItem) => {
        if (item.id === 'edit') {
            router.visit(route('meters.edit', meter.id));
        } else if (item.id === 'delete') {
            onDelete(meter);
        }
    };

    return (
        <div className="relative flex flex-col gap-4 rounded-xl border border-line bg-surface p-4 pr-10 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div className="absolute right-3 top-3">
                <DropdownMenu
                    trigger={
                        <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            className="h-8 w-8"
                            aria-label={`Дії для ${meter.name}`}
                        >
                            <MoreVertical className="h-4 w-4" />
                        </Button>
                    }
                    items={METER_ACTIONS}
                    onSelect={handleMenuSelect}
                />
            </div>

            <div className="space-y-2">
                <div className="flex flex-wrap items-center gap-3">
                    <p className="text-base font-semibold text-foreground">{meter.name}</p>
                    <span className={statusBadge({ active: meter.isActive })}>
                        {meter.isActive ? 'Активний' : 'Неактивний'}
                    </span>
                </div>
                <p className="text-sm text-subtext">
                    Серійний №: <span className="font-medium text-foreground">{meter.serialNumber}</span>
                </p>
                {meter.location ? (
                    <p className="text-sm text-subtext">
                        Локація: <span className="font-medium text-foreground">{meter.location}</span>
                    </p>
                ) : null}
                {meter.serviceProvider ? (
                    <p className="text-sm text-subtext">
                        Провайдер: <span className="font-medium text-foreground">{meter.serviceProvider.name}</span>
                    </p>
                ) : null}
                {meter.utilityType ? (
                    <p className="text-sm text-subtext">
                        Тип: <span className="font-medium text-foreground">{meter.utilityType.displayName}</span>
                    </p>
                ) : null}
            </div>

            {meter.photo ? (
                <div className="relative flex-shrink-0">
                    {thumbnailSrc ? (
                        <img
                            src={thumbnailSrc}
                            alt={`Фото ${meter.name}`}
                            className="h-16 w-16 rounded-lg object-cover"
                        />
                    ) : (
                        <div className="flex h-16 w-16 items-center justify-center rounded-lg border border-line bg-surface">
                            <Spinner size="sm" />
                        </div>
                    )}
                    {meter.photo.is_processing ? (
                        <span className="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-black/60">
                            <Spinner size="xs" className="text-white" />
                        </span>
                    ) : null}
                </div>
            ) : null}
        </div>
    );
}
