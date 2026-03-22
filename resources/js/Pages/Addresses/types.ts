import type { PageProps } from '@/types'

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

export interface PaginatedAddresses {
    data: AddressItem[]
    links: {
        first: string | null
        last: string | null
        prev: string | null
        next: string | null
    }
    meta: {
        current_page: number
        last_page: number
        per_page: number
        total: number
    }
}

export interface IndexPageProps extends PageProps {
    addresses: PaginatedAddresses
}

export interface CreatePageProps extends PageProps {
    regions: { data: AddressRegion[] }
    addressTypes: { data: AddressTypeItem[] }
}

export interface EditPageProps extends PageProps {
    address: { data: AddressItem }
    regions: { data: AddressRegion[] }
    addressTypes: { data: AddressTypeItem[] }
}
