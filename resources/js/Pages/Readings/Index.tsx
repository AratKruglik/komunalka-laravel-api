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
import { useDenormalizeCollection, useDenormalizeAuto } from '@/lib/useDenormalize'
import { formatFullAddressLabel } from '@/lib/formatAddress'
import type { PageProps } from '@/types'
import type { JsonApiCollectionDocument } from '@/types/jsonapi'
import type { Address, Reading } from '@/types/entities'

interface ReadingWithMeterName extends Reading {
  meter: Reading['meter'] & { name: string }
}

interface Props extends PageProps {
  addresses: JsonApiCollectionDocument
  readings: JsonApiCollectionDocument | never[]
  filters: {
    address_id: number | null
  }
}

const dateFormatter = new Intl.DateTimeFormat('uk-UA', {
  day: '2-digit',
  month: 'long',
  year: 'numeric',
})

const numberFormatter = new Intl.NumberFormat('uk-UA', {
  maximumFractionDigits: 2,
})

export default function Index({ addresses, readings, filters }: Props) {
  const [deletingId, setDeletingId] = useState<number | null>(null)
  const addressList = useDenormalizeCollection<Address>(addresses)
  const readingList = useDenormalizeAuto<ReadingWithMeterName>(readings)

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
                    {formatFullAddressLabel(address)}
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
                        {reading.meter.name} &#8470; {reading.meter.serialNumber}
                      </CardTitle>
                      <p className="text-sm text-gray-500">
                        {dateFormatter.format(new Date(reading.readingDate))}
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
                        {numberFormatter.format(reading.readingValue)}
                      </dd>
                    </div>
                    {reading.previousReadingValue !== null ? (
                      <div>
                        <dt className="text-gray-500">Попередні</dt>
                        <dd className="font-semibold text-gray-900">
                          {numberFormatter.format(reading.previousReadingValue)}
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
                    {reading.isEstimated ? (
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
