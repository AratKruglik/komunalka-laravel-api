import type { PageProps } from '@/types'
import type { JsonApiCollectionDocument, JsonApiDocument } from '@/types/jsonapi'

export interface UtilityTypeItem {
    id: number
    slug: string
    displayName: string
    unit: string
    description: string | null
    isActive: boolean
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
    baseRate: string | number
    serviceFee: string | number
    effectiveFrom: string
    effectiveTo: string | null
    notes: string | null
    utilityType: UtilityTypeItem
    currency: CurrencyItem
}

export interface ProviderItem {
    id: number
    name: string
    description: string | null
    phone: string | null
    email: string | null
    website: string | null
    isActive: boolean
    addressId: number
    utilityType: UtilityTypeItem
    tariffs: TariffItem[]
    createdAt: string
    updatedAt: string
}

export interface ProviderAddressItem {
    id: number
    city: string
    street: string
    buildingNumber: string
    apartmentNumber: string | null
    isPrimary: boolean
}

export interface IndexPageProps extends PageProps {
    providers: JsonApiCollectionDocument
}

export interface CreatePageProps extends PageProps {
    addresses: JsonApiCollectionDocument
    utilityTypes: JsonApiCollectionDocument
    currencies: JsonApiCollectionDocument
}

export interface EditPageProps extends PageProps {
    provider: JsonApiDocument
    addresses: JsonApiCollectionDocument
    utilityTypes: JsonApiCollectionDocument
    currencies: JsonApiCollectionDocument
}
