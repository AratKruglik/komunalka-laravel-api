import { useMemo } from 'react';
import { PieChart, Pie, Cell, ResponsiveContainer, Tooltip, Label } from 'recharts';
import { tv } from 'tailwind-variants';

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
    base: 'flex h-full flex-col rounded-xl border border-neutral-200 bg-white p-4 shadow-lg dark:border-slate-800 dark:bg-slate-900 sm:p-6',
});

const UTILITY_COLORS: Record<string, string> = {
    electricity: '#F59E0B',
    gas: '#3B82F6',
    'cold-water': '#06B6D4',
    'hot-water': '#EF4444',
    heating: '#8B5CF6',
};

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
                    <h2 className="text-base font-semibold leading-6 text-neutral-900 dark:text-slate-50 sm:text-lg sm:leading-7">
                        Розподіл витрат
                    </h2>
                    <p className="mt-1 text-sm text-neutral-500 dark:text-slate-400">
                        Загалом:{' '}
                        <span className="font-semibold text-neutral-900 dark:text-slate-100">
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
                                                <text x={cx} y={cy} fill="#111827" textAnchor="middle">
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
                                        backgroundColor: '#fff',
                                        border: '1px solid #E5E7EB',
                                        borderRadius: '8px',
                                        fontSize: '12px',
                                    }}
                                    formatter={(value) => [formatCurrency(Number(value)), 'Сума']}
                                />
                            </PieChart>
                        </ResponsiveContainer>
                    </div>
                </div>

                <div className="flex w-full flex-col justify-center space-y-3 text-sm text-neutral-600 dark:text-slate-300">
                    {chartData.map((item) => (
                        <div
                            key={item.name}
                            className="flex items-center justify-between rounded-lg bg-neutral-50 px-3 py-2 dark:bg-slate-800"
                        >
                            <span className="inline-flex items-center gap-2">
                                <span
                                    className="h-2.5 w-2.5 rounded-full"
                                    style={{ backgroundColor: item.color }}
                                />
                                {item.name}
                            </span>
                            <span className="font-semibold text-neutral-900 dark:text-slate-100">
                                {formatCurrency(item.value)}
                            </span>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
