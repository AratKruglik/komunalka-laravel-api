import { useState } from 'react'
import { Head, router } from '@inertiajs/react'
import { Plus, Trash2 } from 'lucide-react'
import { AuthenticatedLayout } from '@/Layouts/AuthenticatedLayout'
import { PageSectionHeader } from '@/Components/pages'
import {
  Button,
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  Label,
  Select,
  Badge,
  ConfirmDialog,
} from '@/Components/ui'
import type { PageProps } from '@/types'

interface InertiaAddress {
  id: number
  city: string
  street: string
  building_number: string
  apartment_number: string | null
}

interface InertiaReading {
  id: number
  reading_value: number
  reading_date: string
  previous_reading_value: number | null
  consumption: number | null
  notes: string | null
  is_estimated: boolean
  meter: {
    id: number
    serial_number: string
    name: string
  }
}

interface Props extends PageProps {
  addresses: { data: InertiaAddress[] }
  readings: { data: InertiaReading[] } | never[]
  filters: {
    address_id: number | null
  }
}

function getDataArray<T>(value: { data: T[] } | never[]): T[] {
  if (Array.isArray(value)) return value
  return value.data
}

const dateFormatter = new Intl.DateTimeFormat('uk-UA', {
  day: '2-digit',
  month: 'long',
  year: 'numeric',
})

const numberFormatter = new Intl.NumberFormat('uk-UA', {
  maximumFractionDigits: 2,
})

function formatAddressLabel(address: InertiaAddress): string {
  const parts = [address.city, address.street, address.building_number]
  if (address.apartment_number) {
    parts.push(`кв. ${address.apartment_number}`)
  }
  return parts.join(', ')
}

export default function Index({ addresses, readings, filters }: Props) {
  const [deletingId, setDeletingId] = useState<number | null>(null)
  const addressList = addresses.data
  const readingList = getDataArray(readings)

  const handleAddressChange = (event: React.ChangeEvent<HTMLSelectElement>) => {
    router.visit(`/readings?address_id=${event.target.value}`, {
      preserveState: false,
    })
  }

  const handleDelete = () => {
    if (deletingId === null) return

    router.delete(`/readings/${deletingId}`, {
      preserveScroll: true,
      onFinish: () => setDeletingId(null),
    })
  }

  return (
    <AuthenticatedLayout
      pageTitle="Показання лічильників"
      pageSubtitle="Історія внесених показань"
    >
      <Head title="Показання" />

      <div className="space-y-6">
        <Card className="border border-gray-200 shadow-lg">
          <PageSectionHeader
            title="Фільтр за адресою"
            withBorder
            ctaButton={{
              label: 'Внести показання',
              icon: <Plus className="h-4 w-4" />,
              onClick: () =>
                router.visit(
                  `/readings/create${filters.address_id ? `?address_id=${filters.address_id}` : ''}`,
                ),
            }}
          />
          <CardContent>
            <div className="space-y-2">
              <Label htmlFor="address-filter" className="text-sm font-semibold text-gray-700">
                Адреса
              </Label>
              <Select
                id="address-filter"
                value={filters.address_id ?? ''}
                onChange={handleAddressChange}
              >
                <option value="">Оберіть адресу</option>
                {addressList.map((address) => (
                  <option key={address.id} value={address.id}>
                    {formatAddressLabel(address)}
                  </option>
                ))}
              </Select>
            </div>
          </CardContent>
        </Card>

        {readingList.length > 0 ? (
          <div className="space-y-4">
            {readingList.map((reading) => (
              <Card key={reading.id} className="border-gray-100 shadow-md">
                <CardHeader className="flex flex-row items-center justify-between gap-4 border-b border-gray-100 pb-4">
                  <div className="flex items-center gap-3">
                    <div>
                      <CardTitle className="text-base">
                        {reading.meter.name} &#8470; {reading.meter.serial_number}
                      </CardTitle>
                      <p className="text-sm text-gray-500">
                        {dateFormatter.format(new Date(reading.reading_date))}
                      </p>
                    </div>
                  </div>
                  <Button
                    variant="ghost"
                    tone="danger"
                    size="icon"
                    onClick={() => setDeletingId(reading.id)}
                    aria-label="Видалити показання"
                  >
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </CardHeader>
                <CardContent>
                  <dl className="grid grid-cols-2 gap-4 text-sm sm:grid-cols-4">
                    <div>
                      <dt className="text-gray-500">Показання</dt>
                      <dd className="font-semibold text-gray-900">
                        {numberFormatter.format(reading.reading_value)}
                      </dd>
                    </div>
                    {reading.previous_reading_value !== null ? (
                      <div>
                        <dt className="text-gray-500">Попередні</dt>
                        <dd className="font-semibold text-gray-900">
                          {numberFormatter.format(reading.previous_reading_value)}
                        </dd>
                      </div>
                    ) : null}
                    {reading.consumption !== null ? (
                      <div>
                        <dt className="text-gray-500">Споживання</dt>
                        <dd className="font-semibold text-gray-900">
                          {numberFormatter.format(reading.consumption)}
                        </dd>
                      </div>
                    ) : null}
                    {reading.is_estimated ? (
                      <div>
                        <Badge variant="warning">Оцінка</Badge>
                      </div>
                    ) : null}
                  </dl>
                  {reading.notes ? (
                    <p className="mt-3 text-sm text-gray-600">{reading.notes}</p>
                  ) : null}
                </CardContent>
              </Card>
            ))}
          </div>
        ) : filters.address_id ? (
          <Card className="border-dashed border-gray-200 bg-gray-50 text-center shadow-none">
            <CardContent className="py-10">
              <p className="text-lg font-semibold text-gray-800">
                Немає показань для обраної адреси
              </p>
              <p className="mt-2 text-sm text-gray-500">
                Внесіть показання, натиснувши кнопку вище
              </p>
            </CardContent>
          </Card>
        ) : (
          <Card className="border-dashed border-gray-200 bg-gray-50 text-center shadow-none">
            <CardContent className="py-10">
              <p className="text-lg font-semibold text-gray-800">Оберіть адресу</p>
              <p className="mt-2 text-sm text-gray-500">
                Виберіть адресу зі списку, щоб переглянути показання
              </p>
            </CardContent>
          </Card>
        )}
      </div>

      <ConfirmDialog
        isOpen={deletingId !== null}
        onConfirm={handleDelete}
        onClose={() => setDeletingId(null)}
        title="Видалити показання?"
        description="Цю дію неможливо скасувати. Показання буде видалено назавжди."
        confirmLabel="Видалити"
        cancelLabel="Скасувати"
        variant="danger"
      />
    </AuthenticatedLayout>
  )
}
