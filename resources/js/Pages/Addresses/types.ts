import type { PageProps } from '@/types'
import type { JsonApiCollectionDocument, JsonApiDocument } from '@/types/jsonapi'

export interface AddressRegion {
    id: number
    name: string
}

export interface AddressTypeItem {
    id: number
    name: string
    description: string
    icon: string
}

export interface AddressItem {
    id: number
    city: string
    street: string
    building_number: string
    apartment_number: string | null
    zip_code: string | null
    notes: string | null
    is_primary: boolean
    region: AddressRegion
    address_type: AddressTypeItem
    created_at: string
    updated_at: string
}

export interface IndexPageProps extends PageProps {
    addresses: JsonApiCollectionDocument
}

export interface CreatePageProps extends PageProps {
    regions: JsonApiCollectionDocument
    addressTypes: JsonApiCollectionDocument
}

export interface EditPageProps extends PageProps {
    address: JsonApiDocument
    regions: JsonApiCollectionDocument
    addressTypes: JsonApiCollectionDocument
}
