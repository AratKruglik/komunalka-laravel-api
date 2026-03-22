import type { MeterType } from '@/constants/meterTypes'
import type { ProviderTariff } from './providers'

export type ServiceLabel =
  | 'Електроенергія'
  | 'Газ'
  | 'Холодна вода'
  | 'Гаряча вода'
  | 'Опалення'

export const METER_TYPE_TO_SERVICE_LABEL: Record<MeterType, ServiceLabel> = {
  electricity: 'Електроенергія',
  gas: 'Газ',
  coldWater: 'Холодна вода',
  hotWater: 'Гаряча вода',
  heat: 'Опалення',
}

export const SERVICE_LABEL_TO_METER_TYPE: Record<ServiceLabel, MeterType> = {
  Електроенергія: 'electricity',
  Газ: 'gas',
  'Холодна вода': 'coldWater',
  'Гаряча вода': 'hotWater',
  Опалення: 'heat',
}

export const UTILITY_TYPE_ID_TO_METER_TYPE: Record<number, MeterType> = {
  1: 'gas',
  2: 'electricity',
  3: 'coldWater',
  4: 'hotWater',
  5: 'heat',
}

export const METER_TYPE_TO_UTILITY_TYPE_ID: Record<MeterType, number> = {
  gas: 1,
  electricity: 2,
  coldWater: 3,
  hotWater: 4,
  heat: 5,
}

export interface Region {
  id: number
  name: string
}

export interface AddressType {
  id: number
  name: string
  description: string
  icon: string
}

export interface Address {
  id: number
  city: string
  street: string
  buildingNumber: string
  apartmentNumber: string | null
  zipCode: string | null
  notes: string | null
  isPrimary: boolean
  region: Region
  addressType: AddressType
  createdAt: string
  updatedAt: string
}

export interface Provider {
  id: number
  name: string
  serviceType: MeterType
  serviceLabel: ServiceLabel
  unitLabel: string
  tariffs: ProviderTariff[]
  billingCycle: 'monthly' | 'quarterly' | 'annual'
  supportPhone?: string
  supportEmail?: string
  website?: string
  description?: string
  reminderDay?: number
}

export interface Meter {
  id: number
  addressId: number
  serialNumber: string
  name: string
  description: string | null
  modelName: string | null
  location: string | null
  installationDate: string
  initialReading: number | null
  notes: string | null
  isActive: boolean
  utilityType: {
    id: number
    slug: string
    displayName: string
    unit: string
  }
  serviceProvider: {
    id: number
    name: string
  } | null
  photoUrl: string | null
  createdAt: string
  updatedAt: string
}

export interface ReadingPhoto {
  id: number
  originalUrl: string
  optimizedUrl: string
  thumbnailUrl: string
}

export interface Reading {
  id: number
  readingValue: number
  readingDate: string
  previousReadingValue: number | null
  consumption: number | null
  notes: string | null
  isEstimated: boolean
  meter: {
    id: number
    serialNumber: string
  }
  tariff: {
    id: number
    name: string
  } | null
  photos: ReadingPhoto[]
  createdAt: string
  updatedAt: string
}

export interface ConsumptionCalculation {
  meterId: number
  meterName: string
  consumption: string | number
  unit: string
  baseRate: string | number
  serviceFee: string | number
  currencyCode: string
  currencySymbol: string
  totalCost: string | number
}

export interface BatchReadingsResponse {
  readings: Reading[]
  tariffCalculations: ConsumptionCalculation[]
}
