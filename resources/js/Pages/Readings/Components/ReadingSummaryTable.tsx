import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/Components/ui'

interface SummaryRow {
  id: string
  serviceName: string
  previousValue: number | null
  currentValue: number | null
  unit: string
  tariffLabel: string
  tariffPrice: number
}

interface ReadingSummaryTableProps {
  rows: SummaryRow[]
}

const numberFormatter = new Intl.NumberFormat('uk-UA', {
  maximumFractionDigits: 2,
})

const currencyFormatter = new Intl.NumberFormat('uk-UA', {
  style: 'currency',
  currency: 'UAH',
  maximumFractionDigits: 2,
})

export function ReadingSummaryTable({ rows }: ReadingSummaryTableProps) {
  if (rows.length === 0) {
    return null
  }

  const totalCost = rows.reduce((total, row) => {
    if (row.previousValue === null || row.currentValue === null) {
      return total
    }
    const consumption = Math.max(0, row.currentValue - row.previousValue)
    return total + consumption * row.tariffPrice
  }, 0)

  return (
    <Card className="border-border shadow-lg">
      <CardHeader className="space-y-2 border-b border-border pb-4">
        <CardTitle className="text-xl text-text-primary">
          Підсумок показань
        </CardTitle>
        <CardDescription className="text-base text-text-muted">
          Перевірте дані перед відправкою
        </CardDescription>
      </CardHeader>
      <CardContent className="space-y-4 px-4 py-4">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-border text-sm">
            <thead>
              <tr className="text-left text-xs font-semibold uppercase tracking-wide text-text-muted">
                <th className="px-3 py-2">Послуга</th>
                <th className="px-3 py-2">Попередні</th>
                <th className="px-3 py-2">Поточні</th>
                <th className="px-3 py-2">Різниця</th>
                <th className="px-3 py-2">Тариф</th>
                <th className="px-3 py-2">Сума</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-border">
              {rows.map((row) => {
                const hasValues =
                  row.previousValue !== null && row.currentValue !== null
                const consumption = hasValues
                  ? Math.max(0, row.currentValue! - row.previousValue!)
                  : null
                const amount =
                  consumption !== null ? consumption * row.tariffPrice : null
                return (
                  <tr
                    key={row.id}
                    className="text-text-secondary"
                  >
                    <td className="px-3 py-3 font-medium text-text-primary">
                      {row.serviceName}
                    </td>
                    <td className="px-3 py-3">
                      {hasValues
                        ? `${numberFormatter.format(row.previousValue!)} ${row.unit}`
                        : '—'}
                    </td>
                    <td className="px-3 py-3">
                      {hasValues
                        ? `${numberFormatter.format(row.currentValue!)} ${row.unit}`
                        : '—'}
                    </td>
                    <td className="px-3 py-3">
                      {consumption !== null
                        ? `${numberFormatter.format(consumption)} ${row.unit}`
                        : '—'}
                    </td>
                    <td className="px-3 py-3">{row.tariffLabel}</td>
                    <td className="px-3 py-3">
                      {amount !== null ? currencyFormatter.format(amount) : '—'}
                    </td>
                  </tr>
                )
              })}
            </tbody>
          </table>
        </div>
        <p className="text-right text-sm font-semibold text-text-primary">
          Загальна сума: {currencyFormatter.format(totalCost)}
        </p>
      </CardContent>
    </Card>
  )
}
