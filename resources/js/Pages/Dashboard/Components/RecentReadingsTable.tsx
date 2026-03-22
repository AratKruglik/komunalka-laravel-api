import { tv } from 'tailwind-variants';

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

interface RecentReadingsTableProps {
    readings: MeterReadingData[];
}

const card = tv({
    base: 'rounded-xl border border-neutral-200 bg-white p-4 shadow-lg dark:border-slate-800 dark:bg-slate-900 sm:p-5 lg:p-6',
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
                <h2 className="text-base font-semibold leading-6 text-neutral-900 dark:text-slate-50 sm:text-lg sm:leading-7 lg:text-xl">
                    Останні показання
                </h2>
            </header>

            {/* Mobile: Card-based layout */}
            <div className="space-y-3 md:hidden">
                {readings.map((reading) => {
                    const differenceColor =
                        reading.consumption > 0
                            ? 'text-emerald-600'
                            : reading.consumption < 0
                              ? 'text-rose-600'
                              : 'text-neutral-500';

                    return (
                        <div
                            key={reading.id}
                            className="rounded-lg border border-neutral-200 bg-neutral-50 p-3.5 dark:border-slate-800 dark:bg-slate-800"
                        >
                            <div className="mb-3 flex items-center gap-3">
                                <div className="flex-1">
                                    <h3 className="text-sm font-semibold leading-5 text-neutral-800 dark:text-slate-100">
                                        {reading.meter.utility_type.display_name}
                                    </h3>
                                    <p className="mt-0.5 text-xs leading-4 text-neutral-500 dark:text-slate-400">
                                        {formatDate(reading.reading_date)} &middot; {reading.meter.name}
                                    </p>
                                </div>
                            </div>
                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <p className="text-[11px] font-bold uppercase tracking-[0.06em] text-neutral-500">
                                        Показання
                                    </p>
                                    <p className="mt-1 text-sm font-semibold leading-5 text-neutral-800 dark:text-slate-50">
                                        {reading.reading_value} {reading.meter.utility_type.unit}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-[11px] font-bold uppercase tracking-[0.06em] text-neutral-500">
                                        Споживання
                                    </p>
                                    <p className={`mt-1 text-sm font-semibold leading-5 ${differenceColor}`}>
                                        {reading.consumption > 0 ? '+' : ''}
                                        {reading.consumption} {reading.meter.utility_type.unit}
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
                        <tr className="border-b border-neutral-200 dark:border-slate-800">
                            <th className="pb-3 pr-4 text-left text-[11px] font-bold uppercase tracking-[0.06em] text-neutral-500 dark:text-slate-400">
                                Послуга
                            </th>
                            <th className="pb-3 pr-4 text-left text-[11px] font-bold uppercase tracking-[0.06em] text-neutral-500 dark:text-slate-400">
                                Дата
                            </th>
                            <th className="pb-3 pr-4 text-left text-[11px] font-bold uppercase tracking-[0.06em] text-neutral-500 dark:text-slate-400">
                                Показання
                            </th>
                            <th className="pb-3 text-left text-[11px] font-bold uppercase tracking-[0.06em] text-neutral-500 dark:text-slate-400">
                                Споживання
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {readings.map((reading, index) => {
                            const differenceColor =
                                reading.consumption > 0
                                    ? 'text-emerald-600'
                                    : reading.consumption < 0
                                      ? 'text-rose-600'
                                      : 'text-neutral-500 dark:text-slate-400';

                            return (
                                <tr
                                    key={reading.id}
                                    className={`transition-colors hover:bg-neutral-50 dark:hover:bg-slate-800 ${
                                        index !== readings.length - 1
                                            ? 'border-b border-neutral-200 dark:border-slate-800'
                                            : ''
                                    }`}
                                >
                                    <td className="py-4 pr-4">
                                        <span className="text-sm font-semibold leading-5 text-neutral-800 dark:text-slate-100">
                                            {reading.meter.utility_type.display_name}
                                        </span>
                                    </td>
                                    <td className="py-4 pr-4 text-sm leading-5 text-neutral-700 dark:text-slate-300">
                                        {formatDate(reading.reading_date)}
                                    </td>
                                    <td className="py-4 pr-4 text-sm leading-5 text-neutral-700 dark:text-slate-300">
                                        {reading.reading_value} {reading.meter.utility_type.unit}
                                    </td>
                                    <td className={`py-4 text-sm font-semibold leading-5 ${differenceColor}`}>
                                        {reading.consumption > 0 ? '+' : ''}
                                        {reading.consumption} {reading.meter.utility_type.unit}
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
