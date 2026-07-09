import { Fragment, useMemo } from 'react'
import { useForm, router } from '@inertiajs/react'
import {
    BriefcaseBusiness,
    Building2,
    CheckCircle2,
    Home,
    MapPinned,
} from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
import { PageSectionHeader } from '@/Components/pages'
import {
    Button,
    Card,
    CardContent,
    CardFooter,
    Checkbox,
    FormMessage,
    Input,
    Label,
    RadioCard,
    Select,
    Textarea,
} from '@/Components/ui'
import { FormField } from '@/Components/ui/FormField'
import type { Address, Region, AddressType } from '@/types/entities'

type StepStatus = 'completed' | 'current' | 'upcoming'

interface StepDefinition {
    id: string
    title: string
    icon: LucideIcon
}

interface Step extends StepDefinition {
    status: StepStatus
}

interface AddressFormProps {
    regions: Region[]
    addressTypes: AddressType[]
    address?: Address
    submitUrl: string
    submitMethod: 'post' | 'put'
    title: string
    description: string
    submitLabel: string
}

const stepDefinitions: StepDefinition[] = [
    { id: 'type', title: 'Тип нерухомості', icon: Home },
    { id: 'address', title: 'Адреса', icon: MapPinned },
    { id: 'confirmation', title: 'Підтвердження', icon: CheckCircle2 },
]

const ADDRESS_TYPE_ICONS: Record<string, LucideIcon> = {
    apartment: Building2,
    house: Home,
    office: BriefcaseBusiness,
}

const ADDRESS_TYPE_DESCRIPTIONS: Record<string, string> = {
    apartment: 'Багатоквартирний будинок у місті',
    house: 'Окрема садиба або дача',
    office: 'Комерційне або офісне приміщення',
}

export function AddressForm({
    regions,
    addressTypes,
    address,
    submitUrl,
    submitMethod,
    title,
    description,
    submitLabel,
}: AddressFormProps) {
    const form = useForm({
        address_type_id: address ? String(address.addressType.id) : '',
        region_id: address ? String(address.region.id) : '',
        city: address?.city ?? '',
        street: address?.street ?? '',
        building_number: address?.buildingNumber ?? '',
        apartment_number: address?.apartmentNumber ?? '',
        zip_code: address?.zipCode ?? '',
        notes: address?.notes ?? '',
        is_primary: address?.isPrimary ?? false,
    })

    const addressTypeId = form.data.address_type_id
    const isAddressTypeSelected = Boolean(addressTypeId)

    const handleAddressTypeSelect = (typeId: string) => {
        form.setData('address_type_id', typeId)
    }

    const handleSubmit = (event: React.FormEvent) => {
        event.preventDefault()
        if (submitMethod === 'post') {
            form.post(submitUrl)
        } else {
            form.put(submitUrl)
        }
    }

    const handleCancel = () => {
        router.visit('/addresses')
    }

    const selectedAddressType = useMemo(() => {
        if (!addressTypeId) return undefined
        return addressTypes.find((type) => type.id === Number(addressTypeId))
    }, [addressTypeId, addressTypes])

    const steps = useMemo<Step[]>(() => {
        return stepDefinitions.map((definition) => {
            if (definition.id === 'type') {
                return { ...definition, status: isAddressTypeSelected ? 'completed' : 'current' }
            }
            if (definition.id === 'address') {
                return { ...definition, status: isAddressTypeSelected ? 'current' : 'upcoming' }
            }
            return { ...definition, status: 'upcoming' }
        })
    }, [isAddressTypeSelected])

    return (
        <form className="space-y-4" onSubmit={handleSubmit}>
            <Card className="border border-gray-200 shadow-lg dark:border-slate-800 dark:bg-slate-900">
                <PageSectionHeader
                    title={title}
                    description={description}
                    titleClassName="text-2xl font-bold text-dark dark:text-slate-100"
                />

                <CardContent className="space-y-8">
                    {!address ? <FormStepper steps={steps} /> : null}

                    <section className="space-y-4">
                        <div>
                            <Label htmlFor="address_type_id">
                                Тип нерухомості<span className="text-red-500">*</span>
                            </Label>
                            <p className="text-sm text-gray-500 dark:text-slate-400">
                                Оберіть тип нерухомості, для якої додаєте адресу
                            </p>
                        </div>

                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {addressTypes.map((type) => {
                                const Icon = ADDRESS_TYPE_ICONS[type.icon] ?? Home
                                const typeDescription = ADDRESS_TYPE_DESCRIPTIONS[type.icon] ?? type.description
                                return (
                                    <RadioCard
                                        key={type.id}
                                        title={type.name}
                                        description={typeDescription}
                                        icon={Icon}
                                        selected={addressTypeId === String(type.id)}
                                        onClick={() => handleAddressTypeSelect(String(type.id))}
                                    />
                                )
                            })}
                        </div>
                        {form.errors.address_type_id ? (
                            <FormMessage variant="error">{form.errors.address_type_id}</FormMessage>
                        ) : null}
                    </section>

                    <fieldset
                        disabled={!isAddressTypeSelected}
                        className="space-y-6 [&:disabled]:opacity-60"
                        aria-disabled={!isAddressTypeSelected}
                    >
                        <div className="grid gap-6 sm:grid-cols-2">
                            <FormField
                                id="region_id"
                                label="Область"
                                required
                                error={form.errors.region_id}
                            >
                                <Select
                                    id="region_id"
                                    required
                                    value={form.data.region_id}
                                    onChange={(e) => form.setData('region_id', e.target.value)}
                                    isInvalid={Boolean(form.errors.region_id)}
                                >
                                    <option value="" disabled>
                                        Оберіть область
                                    </option>
                                    {regions.map((region) => (
                                        <option key={region.id} value={region.id}>
                                            {region.name}
                                        </option>
                                    ))}
                                </Select>
                            </FormField>

                            <FormField
                                id="city"
                                label="Місто/Населений пункт"
                                required
                                error={form.errors.city}
                            >
                                <Input
                                    id="city"
                                    placeholder="Введіть назву міста або населеного пункту"
                                    required
                                    value={form.data.city}
                                    onChange={(e) => form.setData('city', e.target.value)}
                                    isInvalid={Boolean(form.errors.city)}
                                />
                            </FormField>
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2">
                            <FormField
                                id="street"
                                label="Вулиця"
                                required
                                error={form.errors.street}
                            >
                                <Input
                                    id="street"
                                    placeholder="Введіть назву вулиці"
                                    required
                                    value={form.data.street}
                                    onChange={(e) => form.setData('street', e.target.value)}
                                    isInvalid={Boolean(form.errors.street)}
                                />
                            </FormField>

                            <FormField
                                id="building_number"
                                label="Номер будинку"
                                required
                                error={form.errors.building_number}
                            >
                                <Input
                                    id="building_number"
                                    placeholder="Введіть номер будинку"
                                    required
                                    value={form.data.building_number}
                                    onChange={(e) => form.setData('building_number', e.target.value)}
                                    isInvalid={Boolean(form.errors.building_number)}
                                />
                            </FormField>
                        </div>

                        <div className="grid gap-6 sm:grid-cols-2">
                            <FormField
                                id="apartment_number"
                                label="Номер квартири/офісу"
                                error={form.errors.apartment_number}
                            >
                                <Input
                                    id="apartment_number"
                                    placeholder={
                                        selectedAddressType
                                            ? `Введіть номер для ${selectedAddressType.name.toLowerCase()}`
                                            : 'Введіть номер квартири або офісу'
                                    }
                                    value={form.data.apartment_number}
                                    onChange={(e) => form.setData('apartment_number', e.target.value)}
                                    isInvalid={Boolean(form.errors.apartment_number)}
                                />
                            </FormField>

                            <FormField
                                id="zip_code"
                                label="Поштовий індекс"
                                error={form.errors.zip_code}
                            >
                                <Input
                                    id="zip_code"
                                    type="text"
                                    placeholder="Введіть поштовий індекс (5 цифр)"
                                    inputMode="numeric"
                                    value={form.data.zip_code}
                                    onChange={(e) => form.setData('zip_code', e.target.value)}
                                    isInvalid={Boolean(form.errors.zip_code)}
                                />
                            </FormField>
                        </div>

                        <FormField
                            id="notes"
                            label="Додаткові примітки"
                            error={form.errors.notes}
                        >
                            <Textarea
                                id="notes"
                                placeholder="Додаткова інформація про адресу (необов'язково)"
                                rows={4}
                                value={form.data.notes}
                                onChange={(e) => form.setData('notes', e.target.value)}
                                isInvalid={Boolean(form.errors.notes)}
                            />
                        </FormField>

                        <div className="flex items-center gap-3">
                            <Checkbox
                                id="is_primary"
                                checked={form.data.is_primary}
                                onChange={(e) => form.setData('is_primary', (e.target as HTMLInputElement).checked)}
                            />
                            <Label htmlFor="is_primary" className="!mb-0 cursor-pointer text-dark dark:text-slate-100">
                                Встановити як основну адресу
                            </Label>
                        </div>

                        <p className="text-sm text-gray-500 dark:text-slate-400">
                            <span className="text-red-500">*</span> Обов&apos;язкові поля
                        </p>
                    </fieldset>

                    {!isAddressTypeSelected && !address ? (
                        <p className="rounded-md border border-dashed border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-600 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            Оберіть тип нерухомості, щоб заповнити адресу
                        </p>
                    ) : null}
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
                        disabled={!isAddressTypeSelected}
                    >
                        {submitLabel}
                    </Button>
                </CardFooter>
            </Card>
        </form>
    )
}


interface FormStepperProps {
    steps: Step[]
}

function FormStepper({ steps }: FormStepperProps) {
    const columnTemplate = `repeat(${steps.length * 2 - 1}, minmax(0, 1fr))`

    return (
        <div className="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-slate-800 dark:bg-slate-900">
            <ol
                className="flex flex-col gap-4 md:grid md:items-center md:gap-4"
                style={{ gridTemplateColumns: columnTemplate }}
            >
                {steps.map((step, index) => {
                    const Icon = step.icon
                    const statusClasses = getStatusClasses(step.status)
                    const nextStatus = steps[index + 1]?.status

                    return (
                        <Fragment key={step.id}>
                            <li className="flex flex-col items-center gap-2 text-center md:justify-self-center">
                                <span
                                    className={[
                                        'flex size-12 items-center justify-center rounded-full border-2',
                                        statusClasses.circle,
                                    ].join(' ')}
                                >
                                    <Icon className={statusClasses.icon} />
                                </span>
                                <p className={['text-sm font-medium', statusClasses.label].join(' ')}>
                                    {step.title}
                                </p>
                            </li>
                            {index < steps.length - 1 ? (
                                <Fragment>
                                    <div
                                        aria-hidden
                                        className={['hidden h-1 w-full rounded-full md:block', getConnectorClass(step.status, nextStatus)].join(' ')}
                                    />
                                    <div className="mx-auto block h-6 w-px rounded-full bg-gray-200 dark:bg-slate-700 md:hidden" aria-hidden />
                                </Fragment>
                            ) : null}
                        </Fragment>
                    )
                })}
            </ol>
        </div>
    )
}

function getStatusClasses(status: StepStatus) {
    switch (status) {
        case 'completed':
            return {
                circle: 'border-primary bg-primary/10 text-primary dark:border-amber-300 dark:bg-amber-200/10 dark:text-amber-200',
                icon: 'h-6 w-6',
                label: 'text-dark dark:text-slate-100',
            }
        case 'current':
            return {
                circle: 'border-primary bg-white text-primary dark:border-amber-300 dark:bg-slate-900 dark:text-amber-200',
                icon: 'h-6 w-6',
                label: 'text-dark dark:text-slate-100',
            }
        default:
            return {
                circle: 'border-gray-200 bg-white text-gray-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-400',
                icon: 'h-6 w-6',
                label: 'text-gray-500 dark:text-slate-400',
            }
    }
}

function getConnectorClass(current: StepStatus, next?: StepStatus) {
    if (current === 'completed' && next === 'completed') return 'bg-primary dark:bg-amber-300'
    if (current === 'completed' && next === 'current') return 'bg-primary/70 dark:bg-amber-300/70'
    if (current === 'current') return 'bg-primary/50 dark:bg-amber-300/50'
    return 'bg-gray-200 dark:bg-slate-700'
}
