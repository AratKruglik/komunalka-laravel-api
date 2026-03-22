import type { PageProps } from '@/types'

export interface UtilityTypeItem {
    id: number
    slug: string
    display_name: string
    unit: string
    description: string | null
    is_active: boolean
}

export interface CurrencyItem {
    id: number
    code: string
    name: string
    symbol: string
}

export interface TariffItem {
    id: number
    name: string
    base_rate: string | number
    service_fee: string | number
    effective_from: string
    effective_to: string | null
    notes: string | null
    utility_type: UtilityTypeItem
    currency: CurrencyItem
}

export interface ProviderItem {
    id: number
    name: string
    description: string | null
    phone: string | null
    email: string | null
    website: string | null
    is_active: boolean
    address_id: number
    utility_type: UtilityTypeItem
    tariffs: TariffItem[]
    created_at: string
    updated_at: string
}

export interface AddressItem {
    id: number
    city: string
    street: string
    building_number: string
    apartment_number: string | null
    is_primary: boolean
}

export interface IndexPageProps extends PageProps {
    providers: { data: ProviderItem[] }
}

export interface CreatePageProps extends PageProps {
    addresses: { data: AddressItem[] }
    utilityTypes: { data: UtilityTypeItem[] }
    currencies: { data: CurrencyItem[] }
}

export interface EditPageProps extends PageProps {
    provider: { data: ProviderItem }
    addresses: { data: AddressItem[] }
    utilityTypes: { data: UtilityTypeItem[] }
    currencies: { data: CurrencyItem[] }
}
