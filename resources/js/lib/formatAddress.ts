interface AddressLike {
    city?: string
    street: string
    buildingNumber: string
    apartmentNumber?: string | null
}

export function formatAddressLabel(address: AddressLike): string {
    const parts = [address.street, address.buildingNumber]
    if (address.apartmentNumber) {
        parts.push(`кв. ${address.apartmentNumber}`)
    }
    return parts.join(', ')
}

export function formatFullAddressLabel(address: AddressLike & { city: string }): string {
    const parts = [address.city, address.street, address.buildingNumber]
    if (address.apartmentNumber) {
        parts.push(`кв. ${address.apartmentNumber}`)
    }
    return parts.join(', ')
}
