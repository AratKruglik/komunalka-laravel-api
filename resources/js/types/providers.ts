import type { MeterType } from '@/constants/meterTypes'

export type UtilityServiceType = 'water' | 'gas' | 'electricity' | 'heating'

export type BillingCycle = 'monthly' | 'quarterly' | 'annual'

export interface ProviderTariff {
  id: string
  name: string
  price: number
}

export interface ProviderTemplate {
  id: string
  name: string
  serviceLabel: string
  serviceType: UtilityServiceType
  unitLabel: string
  tariffs: ProviderTariff[]
  billingCycle: BillingCycle
  supportPhone?: string
  supportEmail?: string
  website?: string
  description?: string
  reminderDay?: number
  meterTypes?: MeterType[]
}
