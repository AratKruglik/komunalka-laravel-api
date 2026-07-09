import { Camera, Calendar } from 'lucide-react'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  Input,
  Label,
  PhotoDropzone,
} from '@/Components/ui'
import { SERVICE_CONFIG } from '@/constants/services'
import type { MeterReadingDraftViewModel, TariffEntryViewModel } from '@/viewModels'

interface ReadingCardProps {
  draft: MeterReadingDraftViewModel
  tariffValues: Record<string, string>
  readingDate: string
  photo?: {
    fileName: string | null
    previewUrl: string | null
  }
  onTariffValueChange: (tariffId: string, value: string) => void
  onReadingDateChange: (value: string) => void
  onPhotoSelected: (file: File | null) => void
  onPhotoClear: () => void
}

const numberFormatter = new Intl.NumberFormat('uk-UA', {
  maximumFractionDigits: 2,
})

const currencyFormatter = new Intl.NumberFormat('uk-UA', {
  style: 'currency',
  currency: 'UAH',
  maximumFractionDigits: 2,
})

const dateFormatter = new Intl.DateTimeFormat('uk-UA', {
  day: '2-digit',
  month: 'long',
  year: 'numeric',
})

function formatReadingDate(raw: string): string {
  const date = new Date(raw)
  if (Number.isNaN(date.getTime())) return raw
  return dateFormatter.format(date)
}

function getTariffConsumption(entry: TariffEntryViewModel, currentRaw: string) {
  const parsed = Number(currentRaw)
  if (Number.isNaN(parsed)) return 0
  return Math.max(0, parsed - entry.previousValue)
}

export function ReadingCard({
  draft,
  tariffValues,
  readingDate,
  photo,
  onTariffValueChange,
  onReadingDateChange,
  onPhotoClear,
  onPhotoSelected,
}: ReadingCardProps) {
  const serviceConfig = SERVICE_CONFIG[draft.type]
  const ServiceIcon = serviceConfig?.icon
  const serviceIconBg = serviceConfig?.iconBg ?? 'bg-bg-surface'
  const serviceIconColor = serviceConfig?.iconColor ?? 'text-text-muted'
  const isMultiTariff = draft.tariffEntries.length > 1

  const tariffBreakdown = draft.tariffEntries.map((entry) => {
    const raw = tariffValues[entry.tariffId] ?? String(entry.previousValue)
    const consumption = getTariffConsumption(entry, raw)
    const cost = consumption * entry.tariffPrice
    return { entry, consumption, cost }
  })

  const totalCost = tariffBreakdown.reduce((sum, b) => sum + b.cost, 0)

  const handleDropzoneSelection = (files: FileList | null) => {
    if (!files?.length) {
      onPhotoSelected(null)
      return
    }
    onPhotoSelected(files[0])
  }

  const renderSingleTariff = () => {
    const entry = draft.tariffEntries[0]
    if (!entry) return null
    const currentVal = tariffValues[entry.tariffId] ?? String(entry.previousValue)

    return (
      <>
        <div className="space-y-2">
          <Label htmlFor={`${draft.id}-current`} className="text-sm font-medium">
            Поточні показання
          </Label>
          <Input
            id={`${draft.id}-current`}
            type="number"
            inputMode="decimal"
            value={currentVal}
            onChange={(event) => onTariffValueChange(entry.tariffId, event.target.value)}
            endAdornment={<span className="text-sm text-text-muted">{draft.unit}</span>}
          />
        </div>

        <div className="space-y-2">
          <div className="flex items-center justify-between text-sm">
            <Label htmlFor={`${draft.id}-previous`} className="font-medium">
              Попередні показання
            </Label>
            <span className="text-xs text-text-muted">{formatReadingDate(entry.previousDate)}</span>
          </div>
          <Input
            id={`${draft.id}-previous`}
            value={entry.previousValue}
            readOnly
            className="bg-bg-surface text-text-secondary"
            endAdornment={<span className="text-sm text-text-muted">{draft.unit}</span>}
          />
        </div>
      </>
    )
  }

  const renderMultiTariff = () => (
    <div className="space-y-5">
      {draft.tariffEntries.map((entry) => {
        const currentVal = tariffValues[entry.tariffId] ?? String(entry.previousValue)
        return (
          <div
            key={entry.tariffId}
            className="space-y-3 rounded-lg border border-border bg-bg-surface/50 p-4"
          >
            <p className="text-sm font-semibold text-text-primary">
              {entry.tariffName}
              <span className="ml-2 font-normal text-text-muted">
                ({entry.tariffLabel})
              </span>
            </p>
            <div className="grid gap-3 sm:grid-cols-2">
              <div className="space-y-1">
                <Label
                  htmlFor={`${draft.id}-${entry.tariffId}-current`}
                  className="text-xs font-medium text-text-secondary"
                >
                  Поточні показання
                </Label>
                <Input
                  id={`${draft.id}-${entry.tariffId}-current`}
                  type="number"
                  inputMode="decimal"
                  value={currentVal}
                  onChange={(event) => onTariffValueChange(entry.tariffId, event.target.value)}
                  endAdornment={
                    <span className="text-xs text-text-muted">{draft.unit}</span>
                  }
                />
              </div>
              <div className="space-y-1">
                <div className="flex items-center justify-between">
                  <Label
                    htmlFor={`${draft.id}-${entry.tariffId}-previous`}
                    className="text-xs font-medium text-text-secondary"
                  >
                    Попередні
                  </Label>
                  <span className="text-[10px] text-text-muted">
                    {formatReadingDate(entry.previousDate)}
                  </span>
                </div>
                <Input
                  id={`${draft.id}-${entry.tariffId}-previous`}
                  value={entry.previousValue}
                  readOnly
                  className="bg-bg-surface text-text-secondary"
                  endAdornment={
                    <span className="text-xs text-text-muted">{draft.unit}</span>
                  }
                />
              </div>
            </div>
          </div>
        )
      })}
    </div>
  )

  const renderCalculation = () => {
    if (!isMultiTariff) {
      const b = tariffBreakdown[0]
      if (!b) return null
      return (
        <dl className="mt-4 space-y-3 text-sm text-text-secondary">
          <div className="flex items-center justify-between">
            <dt>Споживання:</dt>
            <dd className="font-semibold text-text-primary">
              {numberFormatter.format(b.consumption)} {draft.unit}
            </dd>
          </div>
          <div className="flex items-center justify-between">
            <dt>Тариф:</dt>
            <dd className="font-semibold text-text-primary">
              {b.entry.tariffLabel}
            </dd>
          </div>
          <div className="flex items-center justify-between border-t border-primary/20 pt-3 text-base">
            <dt className="font-semibold text-text-primary">Вартість:</dt>
            <dd className="font-semibold text-text-primary">
              {currencyFormatter.format(b.cost)}
            </dd>
          </div>
        </dl>
      )
    }

    return (
      <dl className="mt-4 space-y-3 text-sm text-text-secondary">
        {tariffBreakdown.map((b) => (
          <div key={b.entry.tariffId} className="flex items-center justify-between">
            <dt>{b.entry.tariffName}:</dt>
            <dd className="font-semibold text-text-primary">
              {numberFormatter.format(b.consumption)} x{' '}
              {numberFormatter.format(b.entry.tariffPrice)} ={' '}
              {currencyFormatter.format(b.cost)}
            </dd>
          </div>
        ))}
        <div className="flex items-center justify-between border-t border-primary/20 pt-3 text-base">
          <dt className="font-semibold text-text-primary">Загалом:</dt>
          <dd className="font-semibold text-text-primary">
            {currencyFormatter.format(totalCost)}
          </dd>
        </div>
      </dl>
    )
  }

  return (
    <Card className="border-border shadow-lg">
      <CardHeader className="gap-4 border-b border-border pb-5">
        <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
          <div className="flex flex-col gap-3">
            <div className="flex flex-wrap items-center gap-3">
              {ServiceIcon ? (
                <span
                  className={[
                    'inline-flex h-10 w-10 items-center justify-center rounded-full text-lg',
                    serviceIconBg,
                    serviceIconColor,
                  ]
                    .filter(Boolean)
                    .join(' ')}
                >
                  <ServiceIcon className="h-5 w-5" aria-hidden />
                </span>
              ) : null}
              <CardTitle className="text-xl">{draft.serviceName}</CardTitle>
            </div>
            <div className="text-sm text-text-muted">
              <p className="font-medium">{draft.meterLabel}</p>
              <p>&#8470; {draft.meterNumber}</p>
            </div>
          </div>
          <CardDescription className="text-sm text-text-secondary">
            Внесіть актуальні показання та додайте фото лічильника
          </CardDescription>
        </div>
      </CardHeader>

      <CardContent className="grid gap-8 lg:grid-cols-2">
        <div className="space-y-6">
          {isMultiTariff ? renderMultiTariff() : renderSingleTariff()}

          <div className="space-y-2">
            <Label htmlFor={`${draft.id}-date`} className="text-sm font-medium">
              Дата зняття показань
            </Label>
            <Input
              id={`${draft.id}-date`}
              type="date"
              value={readingDate}
              onChange={(event) => onReadingDateChange(event.target.value)}
              endAdornment={<Calendar className="h-4 w-4 text-text-muted" aria-hidden />}
            />
          </div>
        </div>

        <div className="space-y-4">
          <div className="space-y-2">
            <Label className="text-sm font-medium">
              Фото лічильника
            </Label>
            <PhotoDropzone
              id={`${draft.id}-photo`}
              fileName={photo?.fileName ?? null}
              previewUrl={photo?.previewUrl ?? null}
              emptyIcon={<Camera className="h-8 w-8 text-text-muted" aria-hidden />}
              emptyTitle="Перетягніть файл або натисніть, щоб завантажити"
              emptyDescription="Додайте фото для підтвердження показань"
              buttonLabel="Завантажити фото"
              variant="full"
              className="border-primary/30 bg-primary/5"
              onFilesSelected={handleDropzoneSelection}
              onClear={onPhotoClear}
            />
          </div>

          <div className="rounded-xl border border-primary/20 bg-primary/5 p-4">
            <p className="text-base font-semibold text-text-primary">Розрахунок</p>
            {renderCalculation()}
          </div>
        </div>
      </CardContent>
    </Card>
  )
}
