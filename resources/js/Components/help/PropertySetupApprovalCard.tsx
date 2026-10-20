import { Badge, Button, FormMessage } from '@/Components/ui'
import type { ApprovalStatus, HelpApprovalDecision, PropertySetupApproval } from '@/types'

interface Props {
  approval: PropertySetupApproval
  status: ApprovalStatus
  isActive: boolean
  errors: Record<string, string>
  onDecide: (approvalId: string, decision: HelpApprovalDecision) => void
}

const labels: Record<string, string> = {
  id: 'ID', region_id: 'Область', address_type_id: 'Тип адреси', city: 'Місто', street: 'Вулиця',
  building_number: 'Будинок', apartment_number: 'Квартира', zip_code: 'Індекс', notes: 'Примітки',
  utility_type_id: 'Тип послуги', service_provider_id: 'Провайдер', name: 'Назва',
  serial_number: 'Серійний номер', initial_reading: 'Початкове показання', location: 'Розташування',
  phone: 'Телефон', email: 'Email', website: 'Сайт', description: 'Опис', model_name: 'Модель',
  installation_date: 'Дата встановлення', is_primary: 'Основна адреса',
}

function Section({ title, values }: { title: string; values: PropertySetupApproval['address'] }) {
  if (!values) return null

  return (
    <div className="space-y-1.5 border-t border-line pt-2">
      <h4 className="text-sm font-semibold text-foreground">{title}</h4>
      <dl className="space-y-1">
        {Object.entries(values).filter(([, value]) => value !== null).map(([key, value]) => (
          <div key={key} className="flex justify-between gap-3 text-xs">
            <dt className="text-subtext">{labels[key] ?? key}</dt>
            <dd className="break-all text-right text-foreground">
              {key === 'tariffs' && Array.isArray(value)
                ? value.map((tariff) => `${tariff.name ?? 'Тариф'}: ${tariff.base_rate ?? ''}`).join('; ')
                : typeof value === 'boolean' ? (value ? 'Так' : 'Ні') : String(value)}
            </dd>
          </div>
        ))}
      </dl>
    </div>
  )
}

export function PropertySetupApprovalCard({ approval, status, isActive, errors, onDecide }: Props) {
  const expired = Date.parse(approval.expires_at) <= Date.now()
  const pending = status === 'pending' && isActive && !expired
  const submitting = status === 'submitting'

  return (
    <div className="w-full space-y-3 border-t border-line bg-surface px-4 py-3">
      <div className="flex items-center justify-between gap-2">
        <p className="text-sm font-semibold text-foreground">Перевірте налаштування</p>
        {expired || status === 'stale' ? <Badge variant="warning">Неактуально</Badge> : null}
      </div>
      <Section title="Адреса" values={approval.address} />
      <Section title="Провайдер" values={approval.provider} />
      <Section title="Лічильник" values={approval.meter} />
      {approval.warnings.map((warning) => <FormMessage key={warning} variant="error">{warning}</FormMessage>)}
      {Object.values(errors)[0] ? <FormMessage variant="error">{Object.values(errors)[0]}</FormMessage> : null}
      {status === 'pending' || submitting ? (
        <div className="flex flex-wrap gap-2">
          <Button size="sm" variant="primary" loading={submitting} disabled={!pending}
            onClick={() => onDecide(approval.id, { action: 'approve' })}>Створити</Button>
          <Button size="sm" variant="ghost" disabled={!pending}
            onClick={() => onDecide(approval.id, { action: 'reject' })}>Скасувати</Button>
        </div>
      ) : null}
      {pending ? <p className="text-xs text-subtext">Щоб змінити поля, напишіть виправлення у чаті перед підтвердженням.</p> : null}
    </div>
  )
}
