import { tv } from 'tailwind-variants';

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

interface RecentReadingsTableProps {
    readings: MeterReadingData[];
}

const card = tv({
    base: 'rounded-xl border border-line bg-raised p-4 shadow-lg sm:p-5 lg:p-6',
});

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('uk-UA', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};

export function RecentReadingsTable({ readings }: RecentReadingsTableProps) {
    return (
        <section className={card()}>
            <header className="mb-4 flex flex-col gap-2 sm:mb-5 sm:flex-row sm:items-center sm:justify-between">
                <h2 className="text-base font-semibold leading-6 text-foreground sm:text-lg sm:leading-7 lg:text-xl">
                    Останні показання
                </h2>
            </header>

            {/* Mobile: Card-based layout */}
            <div className="space-y-3 md:hidden">
                {readings.map((reading) => {
                    const differenceColor =
                        reading.consumption > 0
                            ? 'text-success'
                            : reading.consumption < 0
                              ? 'text-error'
                              : 'text-muted';

                    return (
                        <div
                            key={reading.id}
                            className="rounded-lg border border-line bg-surface p-3.5"
                        >
                            <div className="mb-3 flex items-center gap-3">
                                <div className="flex-1">
                                    <h3 className="text-sm font-semibold leading-5 text-foreground">
                                        {reading.meter.utilityType.displayName}
                                    </h3>
                                    <p className="mt-0.5 text-xs leading-4 text-muted">
                                        {formatDate(reading.readingDate)} &middot; {reading.meter.name}
                                    </p>
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <p className="text-[11px] font-bold uppercase tracking-[0.06em] text-muted">
                                        Показання
                                    </p>
                                    <p className="mt-1 text-sm font-semibold leading-5 text-foreground">
                                        {reading.readingValue} {reading.meter.utilityType.unit}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-[11px] font-bold uppercase tracking-[0.06em] text-muted">
                                        Споживання
                                    </p>
                                    <p className={`mt-1 text-sm font-semibold leading-5 ${differenceColor}`}>
                                        {reading.consumption > 0 ? '+' : ''}
                                        {reading.consumption} {reading.meter.utilityType.unit}
                                    </p>
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>

            {/* Tablet/Desktop: Table layout */}
            <div className="hidden md:block">
                <table className="w-full table-auto">
                    <thead>
                        <tr className="border-b border-line">
                            <th className="pb-3 pr-4 text-left text-[11px] font-bold uppercase tracking-[0.06em] text-muted">
                                Послуга
                            </th>
                            <th className="pb-3 pr-4 text-left text-[11px] font-bold uppercase tracking-[0.06em] text-muted">
                                Дата
                            </th>
                            <th className="pb-3 pr-4 text-left text-[11px] font-bold uppercase tracking-[0.06em] text-muted">
                                Показання
                            </th>
                            <th className="pb-3 text-left text-[11px] font-bold uppercase tracking-[0.06em] text-muted">
                                Споживання
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {readings.map((reading, index) => {
                            const differenceColor =
                                reading.consumption > 0
                                    ? 'text-success'
                                    : reading.consumption < 0
                                      ? 'text-error'
                                      : 'text-muted';

                            return (
                                <tr
                                    key={reading.id}
                                    className={`transition-colors hover:bg-surface ${
                                        index !== readings.length - 1
                                            ? 'border-b border-line'
                                            : ''
                                    }`}
                                >
                                    <td className="py-4 pr-4">
                                        <span className="text-sm font-semibold leading-5 text-foreground">
                                            {reading.meter.utilityType.displayName}
                                        </span>
                                    </td>
                                    <td className="py-4 pr-4 text-sm leading-5 text-subtext">
                                        {formatDate(reading.readingDate)}
                                    </td>
                                    <td className="py-4 pr-4 text-sm leading-5 text-subtext">
                                        {reading.readingValue} {reading.meter.utilityType.unit}
                                    </td>
                                    <td className={`py-4 text-sm font-semibold leading-5 ${differenceColor}`}>
                                        {reading.consumption > 0 ? '+' : ''}
                                        {reading.consumption} {reading.meter.utilityType.unit}
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            </div>
        </section>
    );
}
