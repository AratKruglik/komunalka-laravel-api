import type { Address, Meter } from '@/types/entities'
import type { MeterType } from '@/constants/meterTypes'
import {
  METER_TYPE_TO_SERVICE_LABEL,
  UTILITY_TYPE_ID_TO_METER_TYPE,
} from '@/types/entities'
import type { LucideIcon } from 'lucide-react'
import { Star } from 'lucide-react'

function getMeterType(meter: Meter): MeterType {
  return UTILITY_TYPE_ID_TO_METER_TYPE[meter.utilityType.id] ?? 'electricity'
}

export interface AddressBadge {
  readonly label: string
  readonly variant: 'primary' | 'muted' | 'outline'
  readonly icon?: LucideIcon
}

export interface AddressServiceTag {
  readonly type: MeterType
  readonly label: string
}

export interface AddressCardViewModel {
  readonly id: number
  readonly title: string
  readonly subtitle: string
  readonly badges: readonly AddressBadge[]
  readonly services: readonly AddressServiceTag[]
  readonly isPrimary: boolean
  readonly addressType: {
    readonly name: string
    readonly icon: string
  }
}

export interface AddressSelectViewModel {
  readonly id: number
  readonly label: string
  readonly description: string
}

const DEFAULT_ADDRESS_TYPE = {
  name: 'Квартира',
  icon: 'apartment',
} as const

export function toAddressCardViewModel(
  address: Address,
  meters: readonly Meter[],
): AddressCardViewModel {
  const apartment = address.apartmentNumber ? `, кв. ${address.apartmentNumber}` : ''
  const title = `${address.street}, ${address.buildingNumber}${apartment}`

  const addressTypeName = address.addressType?.name ?? DEFAULT_ADDRESS_TYPE.name
  const regionName = address.region?.name ?? address.city
  const subtitle = `${addressTypeName} | м. ${address.city}, ${regionName}`

  const badges: AddressBadge[] = []

  if (address.isPrimary) {
    badges.push({ label: 'Основна адреса', variant: 'primary' })
  } else {
    badges.push({ label: 'Зробити основною', variant: 'outline', icon: Star })
  }

  const meterCountLabel =
    meters.length === 1 ? '1 лічильник' : `${meters.length} лічильників`
  badges.push({ label: meterCountLabel, variant: 'muted' })

  const uniqueMeterTypes = new Set<MeterType>()
  meters.forEach((meter) => uniqueMeterTypes.add(getMeterType(meter)))

  const services: AddressServiceTag[] = Array.from(uniqueMeterTypes).map((type) => ({
    type,
    label: METER_TYPE_TO_SERVICE_LABEL[type],
  }))

  return {
    id: address.id,
    title,
    subtitle,
    badges,
    services,
    isPrimary: address.isPrimary,
    addressType: {
      name: address.addressType?.name ?? DEFAULT_ADDRESS_TYPE.name,
      icon: address.addressType?.icon ?? DEFAULT_ADDRESS_TYPE.icon,
    },
  }
}

export function toAddressSelectViewModel(address: Address): AddressSelectViewModel {
  const apartment = address.apartmentNumber ? `, кв. ${address.apartmentNumber}` : ''
  const label = `${address.street}, ${address.buildingNumber}${apartment}`
  const addressTypeName = address.addressType?.name ?? DEFAULT_ADDRESS_TYPE.name
  const regionName = address.region?.name ?? address.city
  const description = `${addressTypeName} | м. ${address.city}, ${regionName}`

  return {
    id: address.id,
    label,
    description,
  }
}

export function toAddressCardViewModels(
  addresses: readonly Address[],
  metersMap: ReadonlyMap<number, readonly Meter[]>,
): readonly AddressCardViewModel[] {
  return addresses.map((address) => {
    const meters = metersMap.get(address.id) || []
    return toAddressCardViewModel(address, meters)
  })
}
