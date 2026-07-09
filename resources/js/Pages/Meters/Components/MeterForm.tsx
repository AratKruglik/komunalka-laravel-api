import { type FormEvent, useMemo, useState } from 'react';
import { useForm } from '@inertiajs/react';
import { CalendarDays, Camera } from 'lucide-react';
import { PageSectionHeader } from '@/Components/pages';
import {
    Button,
    Card,
    CardContent,
    CardFooter,
    FormMessage,
    Input,
    Label,
    PhotoDropzone,
    Select,
    Textarea,
} from '@/Components/ui';
import { formatAddressLabel } from '@/lib/formatAddress';
import type { Address, Meter } from '@/types/entities';

interface UtilityTypeOption {
    id: number;
    slug: string;
    displayName: string;
    unit: string;
}

interface ServiceProviderOption {
    id: number;
    name: string;
    addressId: number;
}

interface MeterFormProps {
    addresses: Address[];
    utilityTypes: UtilityTypeOption[];
    serviceProviders: ServiceProviderOption[];
    meter?: Meter;
}

export function MeterForm({ addresses, utilityTypes, serviceProviders, meter }: MeterFormProps) {
    const isEditing = Boolean(meter);

    const form = useForm({
        address_id: meter?.addressId?.toString() ?? '',
        utility_type_id: meter?.utilityType?.id?.toString() ?? '',
        service_provider_id: meter?.serviceProvider?.id?.toString() ?? '',
        serial_number: meter?.serialNumber ?? '',
        name: meter?.name ?? '',
        description: meter?.description ?? '',
        model_name: meter?.modelName ?? '',
        location: meter?.location ?? '',
        installation_date: meter?.installationDate?.split('T')[0] ?? '',
        initial_reading: meter?.initialReading?.toString() ?? '',
        notes: meter?.notes ?? '',
        is_active: meter?.isActive ?? true,
        photo: null as File | null,
    });

    const [photoPreview, setPhotoPreview] = useState<string | null>(meter?.photoUrl ?? null);

    const addressOptions = useMemo(() => {
        return addresses.map((address) => ({
            value: String(address.id),
            label: formatAddressLabel(address),
        }));
    }, [addresses]);

    const filteredProviders = useMemo(() => {
        if (!form.data.address_id) {
            return [];
        }
        const addressId = Number(form.data.address_id);
        return serviceProviders.filter((sp) => sp.addressId === addressId);
    }, [serviceProviders, form.data.address_id]);

    const selectedUnit = useMemo(() => {
        if (!form.data.utility_type_id) {
            return 'од.';
        }
        const ut = utilityTypes.find((t) => t.id === Number(form.data.utility_type_id));
        return ut?.unit ?? 'од.';
    }, [utilityTypes, form.data.utility_type_id]);

    const handlePhotoSelected = (files: FileList | null) => {
        if (!files || files.length === 0) {
            return;
        }
        const file = files[0];
        form.setData('photo', file);
        setPhotoPreview(URL.createObjectURL(file));
    };

    const handlePhotoClear = () => {
        form.setData('photo', null);
        setPhotoPreview(null);
    };

    const handleSubmit = (event: FormEvent) => {
        event.preventDefault();

        if (isEditing && meter) {
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(route('meters.update', meter.id), {
                forceFormData: true,
            });
        } else {
            form.post(route('meters.store'), {
                forceFormData: true,
            });
        }
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <Card className="shadow-sm">
                <PageSectionHeader
                    title={isEditing ? `Редагування: ${meter?.name}` : 'Додати новий лічильник'}
                    description={
                        isEditing
                            ? 'Змініть дані лічильника та натисніть «Зберегти»'
                            : 'Заповніть форму, щоб додати новий лічильник для обліку комунальних послуг'
                    }
                />

                <CardContent className="space-y-8">
                    {!isEditing ? (
                        <section className="space-y-3">
                            <Label htmlFor="address_id" className="text-base font-semibold">
                                Оберіть адресу <span className="text-error">*</span>
                            </Label>
                            <Select
                                id="address_id"
                                value={form.data.address_id}
                                onChange={(e) => form.setData('address_id', e.target.value)}
                                isInvalid={Boolean(form.errors.address_id)}
                            >
                                <option value="">Оберіть адресу зі списку</option>
                                {addressOptions.map((opt) => (
                                    <option key={opt.value} value={opt.value}>{opt.label}</option>
                                ))}
                            </Select>
                            <FormMessage variant="error">{form.errors.address_id}</FormMessage>
                        </section>
                    ) : null}

                    <section className="grid gap-6 lg:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="utility_type_id">
                                Тип послуги <span className="text-error">*</span>
                            </Label>
                            <Select
                                id="utility_type_id"
                                value={form.data.utility_type_id}
                                onChange={(e) => form.setData('utility_type_id', e.target.value)}
                                isInvalid={Boolean(form.errors.utility_type_id)}
                                disabled={isEditing}
                            >
                                <option value="">Оберіть тип послуги</option>
                                {utilityTypes.map((ut) => (
                                    <option key={ut.id} value={String(ut.id)}>{ut.displayName}</option>
                                ))}
                            </Select>
                            <FormMessage variant="error">{form.errors.utility_type_id}</FormMessage>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="name">
                                Назва <span className="text-error">*</span>
                            </Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                placeholder="Назва лічильника"
                                isInvalid={Boolean(form.errors.name)}
                            />
                            <FormMessage variant="error">{form.errors.name}</FormMessage>
                        </div>
                    </section>

                    <section className="grid gap-6 lg:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="serial_number">
                                Серійний номер <span className="text-error">*</span>
                            </Label>
                            <Input
                                id="serial_number"
                                value={form.data.serial_number}
                                onChange={(e) => form.setData('serial_number', e.target.value)}
                                placeholder="Введіть серійний номер лічильника"
                                isInvalid={Boolean(form.errors.serial_number)}
                            />
                            <FormMessage variant="default" className="text-xs text-muted">
                                Приклад: AE123456789
                            </FormMessage>
                            <FormMessage variant="error">{form.errors.serial_number}</FormMessage>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="location">Розташування лічильника</Label>
                            <Input
                                id="location"
                                value={form.data.location}
                                onChange={(e) => form.setData('location', e.target.value)}
                                placeholder="Наприклад: на кухні біля вхідних дверей"
                            />
                            <FormMessage variant="error">{form.errors.location}</FormMessage>
                        </div>
                    </section>

                    <section className="grid gap-6 lg:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="model_name">Модель/Виробник</Label>
                            <Input
                                id="model_name"
                                value={form.data.model_name}
                                onChange={(e) => form.setData('model_name', e.target.value)}
                                placeholder="Введіть модель або виробника лічильника"
                            />
                            <FormMessage variant="error">{form.errors.model_name}</FormMessage>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="description">Опис</Label>
                            <Input
                                id="description"
                                value={form.data.description}
                                onChange={(e) => form.setData('description', e.target.value)}
                                placeholder="Короткий опис"
                            />
                            <FormMessage variant="error">{form.errors.description}</FormMessage>
                        </div>
                    </section>

                    <section className="grid gap-6 lg:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="installation_date">Дата встановлення</Label>
                            <Input
                                id="installation_date"
                                type="date"
                                value={form.data.installation_date}
                                onChange={(e) => form.setData('installation_date', e.target.value)}
                                isInvalid={Boolean(form.errors.installation_date)}
                                endAdornment={<CalendarDays className="h-5 w-5 text-muted" />}
                            />
                            <FormMessage variant="error">{form.errors.installation_date}</FormMessage>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="initial_reading">
                                Початкові показання <span className="text-error">*</span>
                            </Label>
                            <Input
                                id="initial_reading"
                                type="number"
                                min={0}
                                step="0.01"
                                placeholder="0.00"
                                value={form.data.initial_reading}
                                onChange={(e) => form.setData('initial_reading', e.target.value)}
                                isInvalid={Boolean(form.errors.initial_reading)}
                                endAdornment={<span className="text-sm text-muted">{selectedUnit}</span>}
                            />
                            <FormMessage variant="error">{form.errors.initial_reading}</FormMessage>
                        </div>
                    </section>

                    <section className="grid gap-6 lg:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="service_provider_id">Провайдер послуги</Label>
                            <Select
                                id="service_provider_id"
                                value={form.data.service_provider_id}
                                onChange={(e) => form.setData('service_provider_id', e.target.value)}
                                disabled={filteredProviders.length === 0}
                                isInvalid={Boolean(form.errors.service_provider_id)}
                            >
                                <option value="">
                                    {!form.data.address_id
                                        ? 'Спочатку оберіть адресу'
                                        : filteredProviders.length === 0
                                            ? 'Немає провайдерів для цієї адреси'
                                            : 'Оберіть провайдера'}
                                </option>
                                {filteredProviders.map((sp) => (
                                    <option key={sp.id} value={String(sp.id)}>{sp.name}</option>
                                ))}
                            </Select>
                            <FormMessage variant="error">{form.errors.service_provider_id}</FormMessage>
                        </div>

                        {isEditing ? (
                            <div className="space-y-2">
                                <Label className="text-base font-semibold">Статус</Label>
                                <label className="flex cursor-pointer items-center gap-3">
                                    <input
                                        type="checkbox"
                                        checked={form.data.is_active}
                                        onChange={(e) => form.setData('is_active', e.target.checked)}
                                        className="h-5 w-5 rounded border-line text-primary focus:ring-primary"
                                    />
                                    <span className="text-sm text-subtext">Лічильник активний</span>
                                </label>
                            </div>
                        ) : null}
                    </section>

                    <section className="grid gap-6 lg:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="notes">Додаткові примітки</Label>
                            <Textarea
                                id="notes"
                                rows={4}
                                placeholder="Будь-які додаткові деталі про лічильник"
                                value={form.data.notes}
                                onChange={(e) => form.setData('notes', e.target.value)}
                            />
                            <FormMessage variant="error">{form.errors.notes}</FormMessage>
                        </div>

                        <div className="space-y-2">
                            <Label>Фото лічильника</Label>
                            <PhotoDropzone
                                id="meter-photo"
                                previewUrl={photoPreview}
                                fileName={form.data.photo?.name ?? null}
                                emptyIcon={<Camera className="h-10 w-10 text-muted" />}
                                emptyTitle="Завантажте фото лічильника"
                                emptyDescription="Перетягніть файл або натисніть кнопку"
                                helperText="Максимум 10 МБ"
                                onFilesSelected={handlePhotoSelected}
                                onClear={handlePhotoClear}
                                inputProps={{ accept: 'image/jpeg,image/png,image/gif,image/heic,image/heif' }}
                                previewHeight={160}
                            />
                            <FormMessage variant="error">{form.errors.photo}</FormMessage>
                        </div>
                    </section>
                </CardContent>

                <CardFooter className="flex flex-col gap-4 border-t border-line px-6 py-5 sm:flex-row sm:justify-between">
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={() => window.history.back()}
                    >
                        Скасувати
                    </Button>
                    <Button
                        type="submit"
                        loading={form.processing}
                        loadingText="Збереження..."
                    >
                        {isEditing ? 'Зберегти зміни' : 'Зберегти лічильник'}
                    </Button>
                </CardFooter>
            </Card>
        </form>
    );
}
