import { useMemo, useState, useId, type ChangeEvent } from 'react';
import { Link } from '@inertiajs/react';
import { tv } from 'tailwind-variants';
import { Plus } from 'lucide-react';
import { Label, Select } from '@/Components/ui';

interface AddressOption {
    id: number;
    label: string;
    description?: string;
}

interface WelcomeHeaderProps {
    userName: string;
    addresses: AddressOption[];
    selectedAddressId?: number;
    onAddressChange?: (addressId: number) => void;
}

const welcomeCard = tv({
    base: 'rounded-xl border border-line bg-raised p-4 shadow-lg sm:p-5 lg:p-6',
});

const addButton = tv({
    base: 'inline-flex w-full shrink-0 items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-medium text-on-primary transition-colors hover:bg-primary-dark active:bg-primary-dark sm:w-auto sm:px-5 sm:text-base',
});

export function WelcomeHeader({ userName, addresses, selectedAddressId, onAddressChange }: WelcomeHeaderProps) {
    const selectId = useId();
    const addressOptions = useMemo(() => addresses ?? [], [addresses]);
    const [internalAddressId, setInternalAddressId] = useState<number | undefined>(
        selectedAddressId ?? addressOptions[0]?.id,
    );
    const activeAddressId = selectedAddressId ?? internalAddressId;

    const currentMonth = new Intl.DateTimeFormat('uk-UA', {
        month: 'long',
        year: 'numeric',
    }).format(new Date());

    const selectedAddress = addressOptions.find((option) => option.id === activeAddressId);

    const handleAddressChange = (event: ChangeEvent<HTMLSelectElement>) => {
        const newAddressId = Number(event.target.value);

        if (selectedAddressId === undefined) {
            setInternalAddressId(newAddressId);
        }

        onAddressChange?.(newAddressId);
    };

    return (
        <section className={welcomeCard()}>
            <div className="flex flex-col gap-4 sm:gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div className="space-y-2.5 sm:space-y-3">
                    <h1 className="text-lg font-bold text-foreground sm:text-xl lg:text-2xl">
                        Вітаємо, {userName}!
                    </h1>
                    <p className="text-xs text-subtext sm:text-sm lg:text-base">
                        Ось огляд ваших комунальних послуг за {currentMonth}
                    </p>
                    {addressOptions.length > 0 && (
                        <div className="flex flex-col gap-2">
                            <Label htmlFor={selectId} className="sm:text-base">
                                Адреса обліку
                            </Label>
                            <div className="flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:gap-3">
                                <Select
                                    id={selectId}
                                    value={activeAddressId}
                                    onChange={handleAddressChange}
                                    className="w-full sm:w-72"
                                >
                                    {addressOptions.map((option) => (
                                        <option key={option.id} value={option.id}>
                                            {option.label}
                                        </option>
                                    ))}
                                </Select>
                                {selectedAddress?.description && (
                                    <span className="hidden text-xs text-muted sm:inline lg:text-sm">
                                        {selectedAddress.description}
                                    </span>
                                )}
                            </div>
                        </div>
                    )}
                </div>

                <Link href="/readings/create" className={addButton()}>
                    <Plus className="h-4 w-4 sm:h-5 sm:w-5" strokeWidth={2.2} />
                    Додати показання
                </Link>
            </div>
        </section>
    );
}
