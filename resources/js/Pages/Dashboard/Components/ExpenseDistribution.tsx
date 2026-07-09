import { useMemo } from 'react';
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip, Label } from 'recharts';
import { tv } from 'tailwind-variants';
import { UTILITY_COLORS } from '@/constants/utilityColors';

interface ExpenseItem {
    type: string;
    displayName: string;
    amount: number;
    percentage: number;
}

interface ExpenseDistributionProps {
    data: ExpenseItem[];
}

const card = tv({
    base: 'flex h-full flex-col rounded-xl border border-border bg-bg-raised p-4 shadow-lg sm:p-6',
});

export function ExpenseDistribution({ data }: ExpenseDistributionProps) {
    const chartData = useMemo(
        () =>
            data.map((item) => ({
                name: item.displayName,
                value: item.amount,
                color: UTILITY_COLORS[item.type] ?? '#6B7280',
            })),
        [data],
    );

    const totalExpenses = useMemo(() => chartData.reduce((sum, item) => sum + item.value, 0), [chartData]);

    const formatCurrency = (value: number) => {
        const numericValue = Number.isFinite(value) ? value : 0;
        return `\u20B4${numericValue.toLocaleString('uk-UA')}`;
    };

    return (
        <section className={card()}>
            <header className="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 className="text-base font-semibold leading-6 text-text-primary sm:text-lg sm:leading-7">
                        Розподіл витрат
                    </h2>
                    <p className="mt-1 text-sm text-text-muted">
                        Загалом:{' '}
                        <span className="font-semibold text-text-primary">
                            {formatCurrency(totalExpenses)}
                        </span>
                    </p>
                </div>
            </header>

            <div className="flex flex-1 flex-col gap-6">
                <div className="flex w-full items-center justify-center">
                    <div className="w-full max-w-[280px] sm:max-w-[320px]">
                        <ResponsiveContainer width="100%" height={260}>
                            <PieChart margin={{ top: 16, right: 24, bottom: 16, left: 24 }}>
                                <Pie
                                    data={chartData}
                                    cx="50%"
                                    cy="50%"
                                    labelLine={false}
                                    innerRadius={60}
                                    outerRadius={94}
                                    paddingAngle={3}
                                    dataKey="value"
                                >
                                    {chartData.map((entry, index) => (
                                        <Cell key={`cell-${index}`} fill={entry.color} />
                                    ))}
                                    <Label
                                        position="center"
                                        content={({ viewBox }) => {
                                            if (
                                                !viewBox ||
                                                typeof viewBox !== 'object' ||
                                                !('cx' in viewBox) ||
                                                !('cy' in viewBox)
                                            ) {
                                                return null;
                                            }

                                            const { cx, cy } = viewBox as { cx: number; cy: number };

                                            return (
                                                <text x={cx} y={cy} fill="var(--color-text-primary)" textAnchor="middle">
                                                    <tspan fontSize={12} fontWeight={600} dy={-6}>
                                                        Загалом
                                                    </tspan>
                                                    <tspan x={cx} dy={16} fontSize={14} fontWeight={600}>
                                                        {formatCurrency(totalExpenses)}
                                                    </tspan>
                                                </text>
                                            );
                                        }}
                                    />
                                </Pie>
                                <Tooltip
                                    contentStyle={{
                                        backgroundColor: 'var(--color-bg-raised)',
                                        border: '1px solid var(--color-border)',
                                        borderRadius: '8px',
                                        fontSize: '12px',
                                        color: 'var(--color-text-primary)',
                                    }}
                                    formatter={(value) => [formatCurrency(Number(value)), 'Сума']}
                                />
                            </PieChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                <div className="flex w-full flex-col justify-center space-y-3 text-sm text-text-secondary">
                    {chartData.map((item) => (
                        <div
                            key={item.name}
                            className="flex items-center justify-between rounded-lg bg-bg-surface px-3 py-2"
                        >
                            <span className="inline-flex items-center gap-2">
                                <span
                                    className="h-2.5 w-2.5 rounded-full"
                                    style={{ backgroundColor: item.color }}
                                />
                                {item.name}
                            </span>
                            <span className="font-semibold text-text-primary">
                                {formatCurrency(item.value)}
                            </span>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
