import { useEffect, useMemo, useRef, useState } from 'react'
import { Head, router } from '@inertiajs/react'
import { Plus } from 'lucide-react'
import { AuthenticatedLayout } from '@/Layouts/AuthenticatedLayout'
import { PageSectionHeader } from '@/Components/pages'
import { Button, Card, CardContent, FormMessage, Label, Select } from '@/Components/ui'
import { denormalizeCollection, denormalizeAuto } from '@/lib/jsonapi'
import type { PageProps, Meter, Reading } from '@/types'
import type { ApiServiceProvider } from '@/types/api'
import type { JsonApiCollectionDocument } from '@/types/jsonapi'
import {
  toAddressReadingsSnapshotViewModel,
  type AddressReadingsSnapshotViewModel,
  type MeterReadingDraftViewModel,
} from '@/viewModels'
import { ReadingCard } from './Components/ReadingCard'
import { ReadingSummaryTable } from './Components/ReadingSummaryTable'

interface InertiaAddress {
  id: number
  city: string
  street: string
  building_number: string
  apartment_number: string | null
}

interface InertiaMeter {
  id: number
  serial_number: string
  name: string
  description: string | null
  model_name: string | null
  location: string | null
  installation_date: string
  initial_reading: number | null
  notes: string | null
  is_active: boolean
  address_id: number
  utility_type: {
    id: number
    slug: string
    display_name: string
    unit: string
  }
  service_provider: {
    id: number
    name: string
  } | null
  photo_url: string | null
  created_at: string
  updated_at: string
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
  tariff: {
    id: number
    name: string
  } | null
  photos: Array<{
    id: number
    original_url: string
    optimized_url: string
    thumbnail_url: string
  }>
  created_at: string
  updated_at: string
}

interface InertiaServiceProvider {
  id: number
  name: string
  description: string | null
  phone: string | null
  email: string | null
  website: string | null
  is_active: boolean
  address_id: number
  utility_type: {
    id: number
    slug: string
    display_name: string
    unit: string
    description: string | null
    is_active: boolean
    created_at: string
    updated_at: string
  }
  tariffs: Array<{
    id: number
    name: string
    base_rate: string | number
    service_fee: string | number
    effective_from: string
    effective_to: string | null
    notes: string | null
    utility_type: {
      id: number
      slug: string
      display_name: string
      unit: string
      description: string | null
      is_active: boolean
      created_at: string
      updated_at: string
    }
    currency: {
      id: number
      code: string
      name: string
      symbol: string
      created_at: string
      updated_at: string
    }
    created_at: string
    updated_at: string
  }>
  created_at: string
  updated_at: string
}

interface Props extends PageProps {
  addresses: JsonApiCollectionDocument
  meters: JsonApiCollectionDocument | never[]
  readings: JsonApiCollectionDocument | never[]
  serviceProviders: JsonApiCollectionDocument | never[]
  selectedAddressId: number | null
}

type MeterFormState = Record<
  number,
  {
    tariffValues: Record<string, string>
    readingDate: string
    photo: {
      file: File | null
      fileName: string | null
      previewUrl: string | null
    }
  }
>

function toMeter(m: InertiaMeter): Meter {
  return {
    id: m.id,
    addressId: m.address_id,
    serialNumber: m.serial_number,
    name: m.name,
    description: m.description,
    modelName: m.model_name,
    location: m.location,
    installationDate: m.installation_date,
    initialReading: m.initial_reading,
    notes: m.notes,
    isActive: m.is_active,
    utilityType: {
      id: m.utility_type.id,
      slug: m.utility_type.slug,
      displayName: m.utility_type.display_name,
      unit: m.utility_type.unit,
    },
    serviceProvider: m.service_provider
      ? { id: m.service_provider.id, name: m.service_provider.name }
      : null,
    photoUrl: m.photo_url,
    createdAt: m.created_at,
    updatedAt: m.updated_at,
  }
}

function toReading(r: InertiaReading): Reading {
  return {
    id: r.id,
    readingValue: r.reading_value,
    readingDate: r.reading_date,
    previousReadingValue: r.previous_reading_value,
    consumption: r.consumption,
    notes: r.notes,
    isEstimated: r.is_estimated,
    meter: { id: r.meter.id, serialNumber: r.meter.serial_number },
    tariff: r.tariff,
    photos: r.photos.map((p) => ({
      id: p.id,
      originalUrl: p.original_url,
      optimizedUrl: p.optimized_url,
      thumbnailUrl: p.thumbnail_url,
    })),
    createdAt: r.created_at,
    updatedAt: r.updated_at,
  }
}

function toApiServiceProvider(sp: InertiaServiceProvider): ApiServiceProvider {
  return {
    id: sp.id,
    addressId: sp.address_id,
    name: sp.name,
    description: sp.description,
    phone: sp.phone,
    email: sp.email,
    website: sp.website,
    isActive: sp.is_active,
    utilityType: {
      id: sp.utility_type.id,
      slug: sp.utility_type.slug,
      displayName: sp.utility_type.display_name,
      unit: sp.utility_type.unit,
      description: sp.utility_type.description,
      isActive: sp.utility_type.is_active,
      createdAt: sp.utility_type.created_at,
      updatedAt: sp.utility_type.updated_at,
    },
    tariffs: sp.tariffs.map((t) => ({
      id: t.id,
      name: t.name,
      baseRate: t.base_rate,
      serviceFee: t.service_fee,
      effectiveFrom: t.effective_from,
      effectiveTo: t.effective_to,
      notes: t.notes,
      utilityType: {
        id: t.utility_type.id,
        slug: t.utility_type.slug,
        displayName: t.utility_type.display_name,
        unit: t.utility_type.unit,
        description: t.utility_type.description,
        isActive: t.utility_type.is_active,
        createdAt: t.utility_type.created_at,
        updatedAt: t.utility_type.updated_at,
      },
      currency: {
        id: t.currency.id,
        code: t.currency.code,
        name: t.currency.name,
        symbol: t.currency.symbol,
        createdAt: t.currency.created_at,
        updatedAt: t.currency.updated_at,
      },
      createdAt: t.created_at,
      updatedAt: t.updated_at,
    })),
    createdAt: sp.created_at,
    updatedAt: sp.updated_at,
  }
}

function formatAddressLabel(address: InertiaAddress): string {
  const parts = [address.city, address.street, address.building_number]
  if (address.apartment_number) {
    parts.push(`кв. ${address.apartment_number}`)
  }
  return parts.join(', ')
}

function buildFormState(drafts: readonly MeterReadingDraftViewModel[]): MeterFormState {
  return drafts.reduce<MeterFormState>((acc, draft) => {
    const tariffValues: Record<string, string> = {}
    for (const entry of draft.tariffEntries) {
      tariffValues[entry.tariffId] = String(entry.previousValue)
    }

    acc[draft.id] = {
      tariffValues,
      readingDate: draft.readingDate,
      photo: {
        file: null,
        fileName: null,
        previewUrl: null,
      },
    }
    return acc
  }, {})
}

export default function Create({
  addresses,
  meters,
  readings,
  serviceProviders,
  selectedAddressId,
}: Props) {
  const [isSubmitting, setIsSubmitting] = useState(false)
  const generatedPreviews = useRef<Record<string, string>>({})

  const addressList = useMemo(() => denormalizeCollection<InertiaAddress>(addresses), [addresses])
  const rawMeters = useMemo(() => denormalizeAuto<InertiaMeter>(meters), [meters])
  const rawReadings = useMemo(() => denormalizeAuto<InertiaReading>(readings), [readings])
  const rawProviders = useMemo(() => denormalizeAuto<InertiaServiceProvider>(serviceProviders), [serviceProviders])

  const meterList = useMemo(() => rawMeters.map(toMeter), [rawMeters])
  const readingList = useMemo(() => rawReadings.map(toReading), [rawReadings])
  const providerList = useMemo(() => rawProviders.map(toApiServiceProvider), [rawProviders])

  const currentAddressId = selectedAddressId ?? (addressList[0]?.id ?? null)

  const snapshot = useMemo<AddressReadingsSnapshotViewModel | null>(() => {
    if (currentAddressId === null || meterList.length === 0) {
      return null
    }

    return toAddressReadingsSnapshotViewModel(
      currentAddressId,
      meterList,
      readingList,
      providerList,
    )
  }, [currentAddressId, meterList, readingList, providerList])

  const [forms, setForms] = useState<MeterFormState>(() =>
    buildFormState(snapshot?.meterDrafts ?? []),
  )

  useEffect(() => {
    setForms(buildFormState(snapshot?.meterDrafts ?? []))
    Object.values(generatedPreviews.current).forEach((url) => URL.revokeObjectURL(url))
    generatedPreviews.current = {}
  }, [snapshot])

  useEffect(() => {
    return () => {
      Object.values(generatedPreviews.current).forEach((url) => URL.revokeObjectURL(url))
    }
  }, [])

  useEffect(() => {
    if (addressList.length > 0 && selectedAddressId === null) {
      router.visit(`/readings/create?address_id=${addressList[0].id}`, {
        preserveState: true,
        replace: true,
      })
    }
  }, [addressList, selectedAddressId])

  const handleAddressChange = (event: React.ChangeEvent<HTMLSelectElement>) => {
    router.visit(`/readings/create?address_id=${event.target.value}`, {
      preserveState: false,
    })
  }

  const handleTariffValueChange = (meterId: number, tariffId: string, value: string) => {
    setForms((previous) => ({
      ...previous,
      [meterId]: {
        ...previous[meterId],
        tariffValues: {
          ...previous[meterId]?.tariffValues,
          [tariffId]: value,
        },
      },
    }))
  }

  const handleReadingDateChange = (meterId: number, date: string) => {
    setForms((previous) => ({
      ...previous,
      [meterId]: {
        ...previous[meterId],
        readingDate: date,
      },
    }))
  }

  const handlePhotoSelected = (meterId: number, file: File | null) => {
    setForms((previous) => {
      const nextState = { ...previous }
      if (!nextState[meterId]) {
        return previous
      }

      const previousPreview = generatedPreviews.current[meterId]
      if (previousPreview) {
        URL.revokeObjectURL(previousPreview)
        delete generatedPreviews.current[meterId]
      }

      if (!file) {
        nextState[meterId] = {
          ...nextState[meterId],
          photo: { file: null, fileName: null, previewUrl: null },
        }
        return nextState
      }

      const previewUrl = URL.createObjectURL(file)
      generatedPreviews.current[meterId] = previewUrl

      nextState[meterId] = {
        ...nextState[meterId],
        photo: {
          file,
          fileName: file.name,
          previewUrl,
        },
      }
      return nextState
    })
  }

  const handlePhotoClear = (meterId: number) => {
    setForms((previous) => {
      if (!previous[meterId]) {
        return previous
      }
      const previousPreview = generatedPreviews.current[meterId]
      if (previousPreview) {
        URL.revokeObjectURL(previousPreview)
        delete generatedPreviews.current[meterId]
      }
      return {
        ...previous,
        [meterId]: {
          ...previous[meterId],
          photo: { file: null, fileName: null, previewUrl: null },
        },
      }
    })
  }

  const handleSubmit = (event: React.FormEvent) => {
    event.preventDefault()

    if (currentAddressId === null) return

    const formData = new FormData()

    meterDrafts.forEach((draft, index) => {
      const formState = forms[draft.id]
      if (!formState) return

      const firstEntry = draft.tariffEntries[0]
      if (!firstEntry) return

      const readingValue = Number(
        formState.tariffValues[firstEntry.tariffId] ?? firstEntry.previousValue,
      )

      formData.append(`readings[${index}][meter_id]`, String(draft.id))
      formData.append(`readings[${index}][reading_value]`, String(readingValue))
      formData.append(`readings[${index}][reading_date]`, formState.readingDate)

      if (formState.photo.file) {
        formData.append(`photos[${draft.id}][]`, formState.photo.file)
      }
    })

    setIsSubmitting(true)

    router.post('/readings', formData, {
      forceFormData: true,
      onFinish: () => setIsSubmitting(false),
    })
  }

  const meterDrafts = snapshot?.meterDrafts ?? []

  const summaryRows = meterDrafts.flatMap((draft) => {
    const formState = forms[draft.id]
    return draft.tariffEntries.map((entry) => {
      const currentValue = Number(
        formState?.tariffValues[entry.tariffId] ?? entry.previousValue,
      )
      const showTariffSuffix = draft.tariffEntries.length > 1
      return {
        id: `${draft.id}-${entry.tariffId}-summary`,
        serviceName: showTariffSuffix
          ? `${draft.serviceName} (${entry.tariffName})`
          : draft.serviceName,
        previousValue: entry.previousValue,
        currentValue: Number.isNaN(currentValue) ? null : currentValue,
        unit: draft.unit,
        tariffLabel: entry.tariffLabel,
        tariffPrice: entry.tariffPrice,
      }
    })
  })

  return (
    <AuthenticatedLayout
      pageTitle="Внести показання"
      pageSubtitle="Заповніть форму для кожного лічильника та додайте фото підтвердження"
    >
      <Head title="Внести показання" />

      <div className="space-y-6">
        <Card className="border border-gray-200 shadow-lg">
          <PageSectionHeader
            title="Оберіть адресу для внесення показань"
            description="Всі налаштування, прив'язані до адреси, синхронізуються з вашим обліковим записом"
            withBorder
            ctaButton={{
              label: 'Додати лічильник',
              icon: <Plus className="h-4 w-4" />,
              onClick: () => router.visit('/meters/create'),
            }}
          />
          <CardContent className="space-y-6">
            <div className="space-y-2">
              <Label htmlFor="address-select" className="text-sm font-semibold text-gray-700">
                Адреса
              </Label>
              <Select
                id="address-select"
                value={currentAddressId ?? ''}
                onChange={handleAddressChange}
              >
                {addressList.map((address) => (
                  <option key={address.id} value={address.id}>
                    {formatAddressLabel(address)}
                  </option>
                ))}
              </Select>
            </div>
          </CardContent>
        </Card>

        <form className="space-y-6" onSubmit={handleSubmit}>
          {meterDrafts.length > 0 ? (
            meterDrafts.map((draft) => (
              <ReadingCard
                key={draft.id}
                draft={draft}
                tariffValues={forms[draft.id]?.tariffValues ?? {}}
                readingDate={forms[draft.id]?.readingDate ?? draft.readingDate}
                photo={forms[draft.id]?.photo}
                onTariffValueChange={(tariffId, value) =>
                  handleTariffValueChange(draft.id, tariffId, value)
                }
                onReadingDateChange={(value) => handleReadingDateChange(draft.id, value)}
                onPhotoSelected={(file) => handlePhotoSelected(draft.id, file)}
                onPhotoClear={() => handlePhotoClear(draft.id)}
              />
            ))
          ) : (
            <Card className="border-dashed border-gray-200 bg-gray-50 text-center shadow-none">
              <CardContent className="py-10">
                <p className="text-lg font-semibold text-gray-800">
                  Немає лічильників для вибраної адреси
                </p>
                <p className="mt-2 text-sm text-gray-500">
                  Додайте лічильник у розділі &laquo;Лічильники&raquo;, щоб почати вводити показання
                </p>
              </CardContent>
            </Card>
          )}

          <ReadingSummaryTable rows={summaryRows} />

          <div className="flex justify-end border-t border-gray-100 pt-4">
            <Button
              type="submit"
              tone="primary"
              size="md"
              className="min-w-[220px]"
              loading={isSubmitting}
              loadingText="Збереження..."
              disabled={meterDrafts.length === 0}
            >
              Зберегти
            </Button>
          </div>
        </form>
      </div>
    </AuthenticatedLayout>
  )
}
