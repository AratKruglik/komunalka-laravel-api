import { useId, useState } from 'react'
import { tv } from 'tailwind-variants'
import { Badge, Button, FormMessage, Input, Label, Select } from '@/Components/ui'
import { APPROVAL_LABELS } from '@/constants/assistant'
import {
  formatReadingDate,
  formatReadingNumber,
  isValidReadingDate,
  parseReadingValue,
  todayIsoDate,
} from '@/lib/formatReading'
import type { ApprovalStatus, HelpApprovalDecision, MeterReadingApproval } from '@/types'

const card = tv({
  slots: {
    root: 'w-full space-y-3 border-t border-line bg-surface px-4 py-3',
    heading: 'flex flex-wrap items-center justify-between gap-2',
    title: 'text-sm font-semibold text-foreground',
    rows: 'space-y-1.5',
    row: 'flex flex-wrap items-baseline justify-between gap-x-3 gap-y-0.5',
    rowLabel: 'text-xs text-subtext',
    rowValue: 'text-right text-sm font-medium text-foreground',
    meterName: 'text-sm font-medium text-foreground',
    meterMeta: 'text-xs text-subtext',
    fields: 'space-y-2.5',
    actions: 'flex flex-wrap gap-2',
  },
  variants: {
    size: {
      default: {},
      compact: { title: 'text-[13px]', rowValue: 'text-[13px]' },
    },
  },
})

export interface MeterApprovalCardProps {
  approval: MeterReadingApproval
  status: ApprovalStatus
  isActive: boolean
  errors: Record<string, string>
  onDecide: (approvalId: string, decision: HelpApprovalDecision) => void
  size?: 'default' | 'compact'
}

interface EditDraft {
  value: string
  date: string
  tariffId: string
}

function createDraft(approval: MeterReadingApproval): EditDraft {
  return {
    value: String(approval.reading_value),
    date: approval.reading_date,
    tariffId: approval.tariff ? String(approval.tariff.id) : '',
  }
}

function buildDecision(approval: MeterReadingApproval, draft: EditDraft | null): HelpApprovalDecision {
  if (!draft) {
    return { action: 'approve' }
  }

  const changes: NonNullable<HelpApprovalDecision['arguments']> = {}
  const value = parseReadingValue(draft.value)
  if (value !== null && value !== approval.reading_value) {
    changes.reading_value = value
  }
  if (draft.date !== approval.reading_date) {
    changes.reading_date = draft.date
  }
  if (draft.tariffId !== '' && draft.tariffId !== String(approval.tariff?.id ?? '')) {
    changes.tariff_id = Number(draft.tariffId)
  }

  return Object.keys(changes).length > 0 ? { action: 'edit', arguments: changes } : { action: 'approve' }
}

function validateDraft(draft: EditDraft): Record<string, string> {
  const errors: Record<string, string> = {}
  if (parseReadingValue(draft.value) === null) {
    errors.reading_value = APPROVAL_LABELS.invalidValue
  }
  if (!isValidReadingDate(draft.date)) {
    errors.reading_date = APPROVAL_LABELS.invalidDate
  }
  return errors
}

function resolveConsumption(approval: MeterReadingApproval, draft: EditDraft | null): number | null {
  if (!draft) {
    return approval.consumption
  }

  const isTariffChanged = draft.tariffId !== '' && draft.tariffId !== String(approval.tariff?.id ?? '')
  const value = parseReadingValue(draft.value)
  if (isTariffChanged || value === null) {
    return isTariffChanged ? null : approval.consumption
  }
  return value - approval.previous.value
}

export function MeterApprovalCard({ approval, status, isActive, errors, onDecide, size = 'default' }: MeterApprovalCardProps) {
  const fieldId = useId()
  const [draft, setDraft] = useState<EditDraft | null>(null)
  const [localErrors, setLocalErrors] = useState<Record<string, string>>({})
  const styles = card({ size })

  const { meter } = approval
  const unit = meter.utility_type.unit
  const isExpired = Date.parse(approval.expires_at) <= Date.now()
  const isPending = status === 'pending' && isActive
  const isSubmitting = status === 'submitting'
  const consumption = resolveConsumption(approval, draft)
  const hasDifferentExifDate = approval.exif_date !== null && approval.exif_date !== approval.reading_date
  const fieldError = (name: string): string | undefined => localErrors[name] ?? errors[`arguments.${name}`]
  const generalError = draft ? undefined : Object.values(errors)[0]

  const handleApprove = () => {
    if (draft) {
      const validation = validateDraft(draft)
      setLocalErrors(validation)
      if (Object.keys(validation).length > 0) {
        return
      }
    }
    onDecide(approval.id, buildDecision(approval, draft))
  }

  const handleToggleEdit = () => {
    setLocalErrors({})
    setDraft((current) => (current ? null : createDraft(approval)))
  }

  const updateDraft = (patch: Partial<EditDraft>) => {
    setDraft((current) => (current ? { ...current, ...patch } : current))
  }

  const statusBadge = (): { label: string; variant: 'neutral' | 'warning' } | null => {
    if (status === 'resolved') {
      return { label: APPROVAL_LABELS.resolved, variant: 'neutral' }
    }
    if (status === 'stale') {
      return { label: APPROVAL_LABELS.stale, variant: 'neutral' }
    }
    return isExpired ? { label: APPROVAL_LABELS.expired, variant: 'warning' } : null
  }
  const badge = statusBadge()

  return (
    <div className={styles.root()}>
      <div className={styles.heading()}>
        <p className={styles.title()}>{APPROVAL_LABELS.title}</p>
        {badge ? <Badge variant={badge.variant}>{badge.label}</Badge> : null}
      </div>

      <div>
        <p className={styles.meterName()}>
          {meter.name} · {meter.utility_type.name}
        </p>
        <p className={styles.meterMeta()}>
          {[meter.location, meter.serial_number ? `№ ${meter.serial_number}` : null, meter.address]
            .filter(Boolean)
            .join(' · ')}
        </p>
      </div>

      {draft ? (
        <div className={styles.fields()}>
          <div className="space-y-1">
            <Label htmlFor={`${fieldId}-value`}>{APPROVAL_LABELS.value}, {unit}</Label>
            <Input
              id={`${fieldId}-value`}
              inputMode="decimal"
              value={draft.value}
              isInvalid={Boolean(fieldError('reading_value'))}
              disabled={!isPending}
              onChange={(event) => updateDraft({ value: event.target.value })}
            />
            {fieldError('reading_value') ? <FormMessage variant="error">{fieldError('reading_value')}</FormMessage> : null}
          </div>
          <div className="space-y-1">
            <Label htmlFor={`${fieldId}-date`}>{APPROVAL_LABELS.date}</Label>
            <Input
              id={`${fieldId}-date`}
              type="date"
              max={todayIsoDate()}
              value={draft.date}
              isInvalid={Boolean(fieldError('reading_date'))}
              disabled={!isPending}
              onChange={(event) => updateDraft({ date: event.target.value })}
            />
            {fieldError('reading_date') ? <FormMessage variant="error">{fieldError('reading_date')}</FormMessage> : null}
          </div>
          {approval.tariff_options.length > 1 ? (
            <div className="space-y-1">
              <Label htmlFor={`${fieldId}-tariff`}>{APPROVAL_LABELS.tariff}</Label>
              <Select
                id={`${fieldId}-tariff`}
                value={draft.tariffId}
                isInvalid={Boolean(fieldError('tariff_id'))}
                disabled={!isPending}
                onChange={(event) => updateDraft({ tariffId: event.target.value })}
              >
                {approval.tariff_options.map((option) => (
                  <option key={option.id} value={option.id}>
                    {option.name}
                  </option>
                ))}
              </Select>
              {fieldError('tariff_id') ? <FormMessage variant="error">{fieldError('tariff_id')}</FormMessage> : null}
            </div>
          ) : null}
        </div>
      ) : (
        <dl className={styles.rows()}>
          <div className={styles.row()}>
            <dt className={styles.rowLabel()}>{APPROVAL_LABELS.value}</dt>
            <dd className={styles.rowValue()}>{formatReadingNumber(approval.reading_value, unit)}</dd>
          </div>
          <div className={styles.row()}>
            <dt className={styles.rowLabel()}>{APPROVAL_LABELS.date}</dt>
            <dd className={styles.rowValue()}>
              {formatReadingDate(approval.reading_date)}{' '}
              <Badge variant={approval.date_source === 'exif' ? 'primary' : 'neutral'} className="ml-1 normal-case">
                {approval.date_source === 'exif' ? APPROVAL_LABELS.dateFromExif : APPROVAL_LABELS.dateManual}
              </Badge>
              {hasDifferentExifDate && approval.exif_date ? (
                <span className="block text-xs font-normal text-subtext">
                  {APPROVAL_LABELS.shotDate}: {formatReadingDate(approval.exif_date)}
                </span>
              ) : null}
            </dd>
          </div>
          {approval.tariff ? (
            <div className={styles.row()}>
              <dt className={styles.rowLabel()}>{APPROVAL_LABELS.tariff}</dt>
              <dd className={styles.rowValue()}>{approval.tariff.name}</dd>
            </div>
          ) : null}
        </dl>
      )}

      <dl className={styles.rows()}>
        <div className={styles.row()}>
          <dt className={styles.rowLabel()}>{APPROVAL_LABELS.previous}</dt>
          <dd className={styles.rowValue()}>
            {formatReadingNumber(approval.previous.value, unit)}
            <span className="block text-xs font-normal text-subtext">
              {approval.previous.date ? formatReadingDate(approval.previous.date) : APPROVAL_LABELS.initialValue}
            </span>
          </dd>
        </div>
        <div className={styles.row()}>
          <dt className={styles.rowLabel()}>{APPROVAL_LABELS.consumption}</dt>
          <dd className={styles.rowValue()}>
            {consumption === null ? (
              <span className="text-xs font-normal text-subtext">{APPROVAL_LABELS.consumptionPending}</span>
            ) : (
              formatReadingNumber(consumption, unit)
            )}
          </dd>
        </div>
      </dl>

      {consumption !== null && consumption < 0 ? (
        <FormMessage variant="error" className="text-xs">{APPROVAL_LABELS.negativeConsumption}</FormMessage>
      ) : null}
      {generalError ? <FormMessage variant="error">{generalError}</FormMessage> : null}

      {status === 'pending' || isSubmitting ? (
        <div className={styles.actions()}>
          <Button
            size="sm"
            variant="primary"
            loading={isSubmitting}
            disabled={!isPending || isExpired}
            onClick={handleApprove}
          >
            {draft ? APPROVAL_LABELS.saveChanges : APPROVAL_LABELS.approve}
          </Button>
          <Button size="sm" variant="secondary" disabled={!isPending || isExpired} onClick={handleToggleEdit}>
            {draft ? APPROVAL_LABELS.cancelEdit : APPROVAL_LABELS.edit}
          </Button>
          <Button
            size="sm"
            variant="ghost"
            disabled={!isPending}
            onClick={() => onDecide(approval.id, { action: 'reject' })}
          >
            {APPROVAL_LABELS.reject}
          </Button>
        </div>
      ) : null}
    </div>
  )
}
