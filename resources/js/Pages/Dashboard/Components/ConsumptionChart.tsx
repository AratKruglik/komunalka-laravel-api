import { useState, useMemo } from 'react';
import {
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    Legend,
} from 'recharts';
import { tv } from 'tailwind-variants';
import { UTILITY_COLORS, UTILITY_LABELS } from '@/constants/utilityColors';

interface ConsumptionDataPoint {
    month: string;
    utilityType: string;
    value: number;
}

interface ConsumptionChartProps {
    data: ConsumptionDataPoint[];
}

type PeriodFilter = '3months' | '6months' | '1year';

const chartCard = tv({
    base: 'rounded-xl border border-line bg-raised p-3.5 shadow-lg sm:p-5 lg:p-6',
});

const periodButton = tv({
    base: 'rounded-lg px-3 py-1.5 text-xs font-medium transition-colors sm:px-4 sm:text-sm',
    variants: {
        active: {
            true: 'bg-primary text-on-primary',
            false: 'border border-line bg-raised text-subtext hover:bg-surface',
        },
    },
});

const periodOptions: { value: PeriodFilter; label: string }[] = [
    { value: '1year', label: 'За рік' },
    { value: '6months', label: 'За 6 місяців' },
    { value: '3months', label: 'За 3 місяці' },
];

interface ChartRow {
    month: string;
    [key: string]: string | number;
}

export function ConsumptionChart({ data }: ConsumptionChartProps) {
    const [selectedPeriod, setSelectedPeriod] = useState<PeriodFilter>('6months');

    const { chartData, utilityTypes } = useMemo(() => {
        const monthsLimit = selectedPeriod === '3months' ? 3 : selectedPeriod === '6months' ? 6 : 12;
        const uniqueMonths = [...new Set(data.map((d) => d.month))].sort().slice(-monthsLimit);
        const types = [...new Set(data.map((d) => d.utilityType))];

        const rows: ChartRow[] = uniqueMonths.map((month) => {
            const row: ChartRow = { month };
            for (const type of types) {
                const point = data.find((d) => d.month === month && d.utilityType === type);
                row[type] = point?.value ?? 0;
            }
            return row;
        });

        return { chartData: rows, utilityTypes: types };
    }, [data, selectedPeriod]);

    return (
        <section className={chartCard()}>
            <div className="mb-4 flex flex-col gap-3 sm:mb-5 sm:flex-row sm:items-center sm:justify-between lg:mb-6">
                <h2 className="text-base font-semibold leading-6 text-foreground sm:text-lg sm:leading-7 lg:text-xl">
                    Графік споживання
                </h2>
                <div className="flex flex-wrap gap-1.5 sm:gap-2">
                    {periodOptions.map((option) => (
                        <button
                            key={option.value}
                            type="button"
                            className={periodButton({ active: selectedPeriod === option.value })}
                            onClick={() => setSelectedPeriod(option.value)}
                        >
                            {option.label}
                        </button>
                    ))}
                </div>
            </div>

            <ResponsiveContainer width="100%" height={320}>
                <LineChart data={chartData}>
                    <CartesianGrid strokeDasharray="3 3" stroke="var(--color-line)" />
                    <XAxis dataKey="month" stroke="var(--color-muted)" style={{ fontSize: '12px' }} />
                    <YAxis stroke="var(--color-muted)" style={{ fontSize: '12px' }} />
                    <Tooltip
                        contentStyle={{
                            backgroundColor: 'var(--color-raised)',
                            border: '1px solid var(--color-line)',
                            borderRadius: '8px',
                            fontSize: '12px',
                            color: 'var(--color-foreground)',
                        }}
                    />
                    <Legend wrapperStyle={{ fontSize: '12px' }} iconType="line" />
                    {utilityTypes.map((type) => (
                        <Line
                            key={type}
                            type="monotone"
                            dataKey={type}
                            name={UTILITY_LABELS[type] ?? type}
                            stroke={UTILITY_COLORS[type] ?? '#6B7280'}
                            strokeWidth={3}
                            dot={{
                                fill: UTILITY_COLORS[type] ?? '#6B7280',
                                stroke: UTILITY_COLORS[type] ?? '#6B7280',
                                strokeWidth: 2,
                                r: 5,
                            }}
                            activeDot={{ r: 7 }}
                        />
                    ))}
                </LineChart>
            </ResponsiveContainer>
        </section>
    );
}
