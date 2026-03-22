export interface ApiUtilityType {
  id: number
  slug: string
  displayName: string
  unit: string
  description: string | null
  isActive: boolean
  createdAt: string
  updatedAt: string
}

export interface ApiCurrency {
  id: number
  code: string
  name: string
  symbol: string
  createdAt: string
  updatedAt: string
}

export interface ApiTariff {
  id: number
  name: string
  baseRate: string | number
  serviceFee: string | number
  effectiveFrom: string
  effectiveTo: string | null
  notes: string | null
  utilityType: ApiUtilityType
  currency: ApiCurrency
  createdAt: string
  updatedAt: string
}

export interface ApiServiceProvider {
  id: number
  addressId: number
  name: string
  description: string | null
  phone: string | null
  email: string | null
  website: string | null
  isActive: boolean
  utilityType: ApiUtilityType
  tariffs: ApiTariff[]
  createdAt: string
  updatedAt: string
}

export interface CreateTariffRequest {
  utilityTypeId: number
  currencyId: number
  name: string
  baseRate: number
  serviceFee?: number
  effectiveFrom: string
  effectiveTo?: string | null
  notes?: string | null
}

export interface UpdateTariffRequest {
  name?: string
  baseRate?: number
  serviceFee?: number
  notes?: string | null
}

export interface CreateServiceProviderRequest {
  addressId: number
  utilityTypeId: number
  name: string
  description?: string | null
  phone?: string | null
  email?: string | null
  website?: string | null
  isActive?: boolean
  tariffs: CreateTariffRequest[]
}

export interface UpdateServiceProviderRequest {
  name?: string
  description?: string | null
  phone?: string | null
  email?: string | null
  website?: string | null
  isActive?: boolean
  utilityTypeId?: number
}

