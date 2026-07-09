import { useMemo } from 'react'
import { useForm, router } from '@inertiajs/react'
import { Plus, Trash2 } from 'lucide-react'
import { PageSectionHeader } from '@/Components/pages'
import {
    Button,
    Card,
    CardContent,
    CardFooter,
    Input,
    Select,
    Textarea,
} from '@/Components/ui'
import { FormField } from '@/Components/ui/FormField'
import { formatAddressLabel } from '@/lib/formatAddress'
import type { CurrencyItem, ProviderAddressItem, ProviderItem, UtilityTypeItem } from '../types'

interface ProviderFormProps {
    addresses: ProviderAddressItem[]
    utilityTypes: UtilityTypeItem[]
    currencies: CurrencyItem[]
    provider?: ProviderItem
    submitUrl: string
    submitMethod: 'post' | 'put'
    title: string
    description: string
    submitLabel: string
}

interface TariffFormData {
    utility_type_id: string
    currency_id: string
    name: string
    base_rate: string
    service_fee: string
    effective_from: string
    effective_to: string
    notes: string
}

interface FormData {
    address_id: string
    utility_type_id: string
    name: string
    description: string
    phone: string
    email: string
    website: string
    is_active: boolean
    tariffs: TariffFormData[]
}

function createEmptyTariff(utilityTypeId: string, currencyId: string): TariffFormData {
    return {
        utility_type_id: utilityTypeId,
        currency_id: currencyId,
        name: 'Базовий тариф',
        base_rate: '',
        service_fee: '0',
        effective_from: new Date().toISOString().split('T')[0],
        effective_to: '',
        notes: '',
    }
}

export function ProviderForm({
    addresses,
    utilityTypes,
    currencies,
    provider,
    submitUrl,
    submitMethod,
    title,
    description,
    submitLabel,
}: ProviderFormProps) {
    const defaultCurrencyId = currencies.length > 0 ? String(currencies[0].id) : ''

    const initialTariffs: TariffFormData[] = provider?.tariffs?.length
        ? provider.tariffs.map((t) => ({
              utility_type_id: String(t.utilityType?.id ?? provider.utilityType?.id ?? ''),
              currency_id: String(t.currency?.id ?? defaultCurrencyId),
              name: t.name,
              base_rate: String(t.baseRate),
              service_fee: String(t.serviceFee ?? '0'),
              effective_from: t.effectiveFrom ? String(t.effectiveFrom).split('T')[0] : '',
              effective_to: t.effectiveTo ? String(t.effectiveTo).split('T')[0] : '',
              notes: t.notes ?? '',
          }))
        : [createEmptyTariff(provider?.utilityType ? String(provider.utilityType.id) : '', defaultCurrencyId)]

    const form = useForm<FormData>({
        address_id: provider ? String(provider.addressId) : (addresses.length > 0 ? String(addresses[0].id) : ''),
        utility_type_id: provider?.utilityType ? String(provider.utilityType.id) : '',
        name: provider?.name ?? '',
        description: provider?.description ?? '',
        phone: provider?.phone ?? '',
        email: provider?.email ?? '',
        website: provider?.website ?? '',
        is_active: provider?.isActive ?? true,
        tariffs: initialTariffs,
    })

    const handleUtilityTypeChange = (value: string) => {
        form.setData((data) => ({
            ...data,
            utility_type_id: value,
            tariffs: data.tariffs.map((tariff) => ({
                ...tariff,
                utility_type_id: value,
            })),
        }))
    }

    const selectedUtilityType = useMemo(() => {
        return utilityTypes.find((ut) => String(ut.id) === form.data.utility_type_id)
    }, [utilityTypes, form.data.utility_type_id])

    const handleSubmit = (event: React.FormEvent) => {
        event.preventDefault()
        if (submitMethod === 'post') {
            form.post(submitUrl)
        } else {
            form.put(submitUrl)
        }
    }

    const handleCancel = () => {
        router.visit('/providers')
    }

    const addTariff = () => {
        form.setData('tariffs', [
            ...form.data.tariffs,
            createEmptyTariff(form.data.utility_type_id, defaultCurrencyId),
        ])
    }

    const removeTariff = (index: number) => {
        form.setData(
            'tariffs',
            form.data.tariffs.filter((_, i) => i !== index),
        )
    }

    const updateTariff = (index: number, field: keyof TariffFormData, value: string) => {
        const updated = [...form.data.tariffs]
        updated[index] = { ...updated[index], [field]: value }
        form.setData('tariffs', updated)
    }

    const formatAddressDisplay = (address: ProviderAddressItem): string => {
        return formatAddressLabel(address)
    }

    return (
        <form className="space-y-4" onSubmit={handleSubmit}>
            <Card className="shadow-lg">
                <PageSectionHeader
                    title={title}
                    description={description}
                    titleClassName="text-2xl font-bold text-foreground"
                />

                <CardContent className="space-y-8">
                    <div className="grid gap-6 sm:grid-cols-2">
                        <FormField
                            id="address_id"
                            label="Адреса"
                            required
                            error={form.errors.address_id}
                        >
                            <Select
                                id="address_id"
                                value={form.data.address_id}
                                onChange={(e) => form.setData('address_id', e.target.value)}
                                isInvalid={Boolean(form.errors.address_id)}
                                disabled={Boolean(provider)}
                            >
                                <option value="" disabled>
                                    Оберіть адресу
                                </option>
                                {addresses.map((address) => (
                                    <option key={address.id} value={address.id}>
                                        {formatAddressDisplay(address)}
                                        {address.isPrimary ? ' (основна)' : ''}
                                    </option>
                                ))}
                            </Select>
                        </FormField>

                        <FormField
                            id="utility_type_id"
                            label="Тип послуги"
                            required
                            error={form.errors.utility_type_id}
                        >
                            <Select
                                id="utility_type_id"
                                value={form.data.utility_type_id}
                                onChange={(e) => handleUtilityTypeChange(e.target.value)}
                                isInvalid={Boolean(form.errors.utility_type_id)}
                            >
                                <option value="" disabled>
                                    Оберіть тип послуги
                                </option>
                                {utilityTypes.map((ut) => (
                                    <option key={ut.id} value={ut.id}>
                                        {ut.displayName}
                                    </option>
                                ))}
                            </Select>
                        </FormField>
                    </div>

                    <FormField
                        id="name"
                        label="Назва провайдера"
                        required
                        error={form.errors.name}
                    >
                        <Input
                            id="name"
                            placeholder="Наприклад, Київводоканал"
                            value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)}
                            isInvalid={Boolean(form.errors.name)}
                        />
                    </FormField>

                    {submitMethod === 'post' && (
                        <div className="space-y-4 rounded-xl border border-line bg-surface p-4">
                            <div className="flex flex-wrap items-start justify-between gap-3">
                                <div className="space-y-1">
                                    <p className="text-sm font-semibold text-foreground">
                                        Тарифи провайдера
                                    </p>
                                    <p className="text-sm text-muted">
                                        Додайте денний, нічний чи інші плани
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    size="sm"
                                    className="gap-2"
                                    onClick={addTariff}
                                >
                                    <Plus className="h-4 w-4" />
                                    Додати тариф
                                </Button>
                            </div>

                            <div className="space-y-3">
                                {form.data.tariffs.map((tariff, index) => (
                                    <div
                                        key={index}
                                        className="rounded-lg border border-line bg-raised p-4 shadow-md"
                                    >
                                        <div className="flex items-center justify-between gap-3">
                                            <div className="flex items-center gap-3 text-sm font-semibold text-foreground">
                                                <span className="grid size-9 place-items-center rounded-full bg-surface text-subtext">
                                                    {index + 1}
                                                </span>
                                                <span>{tariff.name || 'Новий тариф'}</span>
                                            </div>
                                            {form.data.tariffs.length > 1 && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    className="text-sm"
                                                    onClick={() => removeTariff(index)}
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                    Видалити
                                                </Button>
                                            )}
                                        </div>

                                        <div className="grid gap-4 pt-2 md:grid-cols-3 md:gap-6">
                                            <FormField
                                                id={`tariff-name-${index}`}
                                                label="Назва тарифу"
                                                required
                                            >
                                                <Input
                                                    id={`tariff-name-${index}`}
                                                    placeholder="Наприклад: Денний, Нічний"
                                                    value={tariff.name}
                                                    onChange={(e) => updateTariff(index, 'name', e.target.value)}
                                                />
                                            </FormField>

                                            <FormField
                                                id={`tariff-rate-${index}`}
                                                label="Базова ставка"
                                                required
                                            >
                                                <Input
                                                    id={`tariff-rate-${index}`}
                                                    type="number"
                                                    placeholder="12.45"
                                                    step="0.01"
                                                    min="0"
                                                    inputMode="decimal"
                                                    value={tariff.base_rate}
                                                    onChange={(e) => updateTariff(index, 'base_rate', e.target.value)}
                                                    endAdornment={
                                                        <span className="text-sm font-medium text-subtext">
                                                            грн/{selectedUtilityType?.unit ?? 'од.'}
                                                        </span>
                                                    }
                                                />
                                            </FormField>

                                            <FormField
                                                id={`tariff-fee-${index}`}
                                                label="Абонплата"
                                            >
                                                <Input
                                                    id={`tariff-fee-${index}`}
                                                    type="number"
                                                    placeholder="0"
                                                    step="0.01"
                                                    min="0"
                                                    inputMode="decimal"
                                                    value={tariff.service_fee}
                                                    onChange={(e) => updateTariff(index, 'service_fee', e.target.value)}
                                                    endAdornment={
                                                        <span className="text-sm font-medium text-subtext">
                                                            грн
                                                        </span>
                                                    }
                                                />
                                            </FormField>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    <div className="grid gap-6 sm:grid-cols-2">
                        <FormField
                            id="phone"
                            label="Телефон підтримки"
                            error={form.errors.phone}
                        >
                            <Input
                                id="phone"
                                type="tel"
                                inputMode="tel"
                                placeholder="+380 44 123 45 67"
                                value={form.data.phone}
                                onChange={(e) => form.setData('phone', e.target.value)}
                                isInvalid={Boolean(form.errors.phone)}
                            />
                        </FormField>

                        <FormField
                            id="email"
                            label="E-mail для звернень"
                            error={form.errors.email}
                        >
                            <Input
                                id="email"
                                type="email"
                                placeholder="support@example.com"
                                value={form.data.email}
                                onChange={(e) => form.setData('email', e.target.value)}
                                isInvalid={Boolean(form.errors.email)}
                            />
                        </FormField>
                    </div>

                    <FormField
                        id="website"
                        label="Офіційний сайт"
                        error={form.errors.website}
                    >
                        <Input
                            id="website"
                            type="url"
                            placeholder="https://..."
                            value={form.data.website}
                            onChange={(e) => form.setData('website', e.target.value)}
                            isInvalid={Boolean(form.errors.website)}
                        />
                    </FormField>

                    <FormField
                        id="description"
                        label="Нотатки"
                        error={form.errors.description}
                    >
                        <Textarea
                            id="description"
                            rows={4}
                            placeholder="Додаткові деталі про провайдера або тариф"
                            value={form.data.description}
                            onChange={(e) => form.setData('description', e.target.value)}
                            isInvalid={Boolean(form.errors.description)}
                        />
                    </FormField>

                    <p className="text-sm text-muted">
                        <span className="text-error">*</span> Обов&apos;язкові поля
                    </p>
                </CardContent>

                <CardFooter className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={handleCancel}
                        disabled={form.processing}
                    >
                        Скасувати
                    </Button>
                    <Button
                        type="submit"
                        loading={form.processing}
                        loadingText="Збереження..."
                    >
                        {submitLabel}
                    </Button>
                </CardFooter>
            </Card>
        </form>
    )
}
