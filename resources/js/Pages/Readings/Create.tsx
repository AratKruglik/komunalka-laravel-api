import { useEffect, useMemo, useRef, useState } from 'react'
import { Head, router } from '@inertiajs/react'
import { Plus } from 'lucide-react'
import { AuthenticatedLayout } from '@/Layouts/AuthenticatedLayout'
import { PageSectionHeader } from '@/Components/pages'
import { Button, Card, CardContent, FormMessage, Label, Select } from '@/Components/ui'
import { useDenormalizeCollection, useDenormalizeAuto } from '@/lib/useDenormalize'
import { formatFullAddressLabel } from '@/lib/formatAddress'
import type { PageProps, Meter, Reading } from '@/types'
import type { ApiServiceProvider } from '@/types/api'
import type { Address } from '@/types/entities'
import type { JsonApiCollectionDocument } from '@/types/jsonapi'
import {
  toAddressReadingsSnapshotViewModel,
  type AddressReadingsSnapshotViewModel,
  type MeterReadingDraftViewModel,
} from '@/viewModels'
import { ReadingCard } from './Components/ReadingCard'
import { ReadingSummaryTable } from './Components/ReadingSummaryTable'

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
  const [submitError, setSubmitError] = useState<string | null>(null)
  const generatedPreviews = useRef<Record<string, string>>({})

  const addressList = useDenormalizeCollection<Address>(addresses)
  const meterList = useDenormalizeAuto<Meter>(meters)
  const readingList = useDenormalizeAuto<Reading>(readings)
  const providerList = useDenormalizeAuto<ApiServiceProvider>(serviceProviders)

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
    let readingIndex = 0

    meterDrafts.forEach((draft) => {
      const formState = forms[draft.id]
      if (!formState) return

      draft.tariffEntries.forEach((entry) => {
        const readingValue = Number(
          formState.tariffValues[entry.tariffId] ?? entry.previousValue,
        )

        formData.append(`readings[${readingIndex}][meter_id]`, String(draft.id))
        formData.append(`readings[${readingIndex}][reading_value]`, String(readingValue))
        formData.append(`readings[${readingIndex}][reading_date]`, formState.readingDate)

        if (entry.tariffId !== '') {
          formData.append(
            `readings[${readingIndex}][tariff_id]`,
            String(parseInt(entry.tariffId, 10)),
          )
        }

        readingIndex++
      })

      if (formState.photo.file) {
        formData.append(`photos[${draft.id}][]`, formState.photo.file)
      }
    })

    setIsSubmitting(true)
    setSubmitError(null)

    router.post('/readings', formData, {
      forceFormData: true,
      onFinish: () => setIsSubmitting(false),
      onError: (errors) => {
        const firstError = Object.values(errors)[0]
        setSubmitError(firstError ?? 'Сталася помилка. Перевірте введені дані.')
      },
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
                    {formatFullAddressLabel(address)}
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

          <div className="flex flex-col items-end gap-2 border-t border-gray-100 pt-4">
            {submitError && (
              <FormMessage variant="error">{submitError}</FormMessage>
            )}
            <Button
              type="submit"
              variant="primary"
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
