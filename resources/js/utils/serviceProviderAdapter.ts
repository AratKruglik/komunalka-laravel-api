import type { ApiServiceProvider, ApiTariff, ApiUtilityType } from '@/types/api'
import type { Provider, ServiceLabel } from '@/types/entities'
import type { ProviderTariff } from '@/types/providers'
import type { MeterType } from '@/constants/meterTypes'
import { UTILITY_TYPE_ID_TO_METER_TYPE, METER_TYPE_TO_SERVICE_LABEL } from '@/types/entities'

const UTILITY_SLUG_TO_METER_TYPE: Record<string, MeterType> = {
  electricity: 'electricity',
  gas: 'gas',
  cold_water: 'coldWater',
  coldwater: 'coldWater',
  hot_water: 'hotWater',
  hotwater: 'hotWater',
  heat: 'heat',
  heating: 'heat',
}

export function utilityTypeToMeterType(utilityType: ApiUtilityType): MeterType {
  return UTILITY_SLUG_TO_METER_TYPE[utilityType.slug] ?? 'electricity'
}

export function utilityTypeIdToMeterType(utilityTypeId: number): MeterType {
  return UTILITY_TYPE_ID_TO_METER_TYPE[utilityTypeId] ?? 'electricity'
}

export function meterTypeToServiceLabel(meterType: MeterType): ServiceLabel {
  return METER_TYPE_TO_SERVICE_LABEL[meterType]
}

export function adaptApiTariffToLegacy(tariff: ApiTariff): ProviderTariff {
  return {
    id: String(tariff.id),
    name: tariff.name,
    price: Number(tariff.baseRate),
  }
}

export function formatApiTariffLabel(tariff: ApiTariff): string {
  const price = Number(tariff.baseRate).toFixed(2)
  const symbol = tariff.currency.symbol
  const unit = getUnitForUtilityType(tariff.utilityType.id)
  return `${price} ${symbol}/${unit}`
}

function getUnitForUtilityType(utilityTypeId: number): string {
  const units: Record<number, string> = {
    1: 'м³',
    2: 'кВт·год',
    3: 'м³',
    4: 'м³',
    5: 'Гкал',
  }
  return units[utilityTypeId] ?? 'од'
}

export function adaptApiProviderToLegacy(apiProvider: ApiServiceProvider): Provider {
  const meterType = utilityTypeToMeterType(apiProvider.utilityType)
  const serviceLabel = meterTypeToServiceLabel(meterType)
  const unit = getUnitForUtilityType(apiProvider.utilityType.id)

  return {
    id: apiProvider.id,
    name: apiProvider.name,
    serviceType: meterType,
    serviceLabel: serviceLabel,
    unitLabel: unit,
    tariffs: apiProvider.tariffs.map(adaptApiTariffToLegacy),
    billingCycle: 'monthly',
    supportPhone: apiProvider.phone ?? undefined,
    supportEmail: apiProvider.email ?? undefined,
    website: apiProvider.website ?? undefined,
    description: apiProvider.description ?? undefined,
  }
}

export function adaptApiProvidersToLegacy(apiProviders: ApiServiceProvider[]): Provider[] {
  return apiProviders.map(adaptApiProviderToLegacy)
}

export function getPrimaryTariffFromApiProvider(
  provider: ApiServiceProvider
): ApiTariff | undefined {
  return provider.tariffs[0]
}

export function getActiveTariff(
  provider: ApiServiceProvider,
  date: Date = new Date()
): ApiTariff | undefined {
  const dateStr = date.toISOString()

  return provider.tariffs.find((tariff) => {
    const isAfterStart = tariff.effectiveFrom <= dateStr
    const isBeforeEnd = !tariff.effectiveTo || tariff.effectiveTo >= dateStr
    return isAfterStart && isBeforeEnd
  })
}

export function calculateCost(consumption: number, tariff: ApiTariff): number {
  const consumptionCost = consumption * Number(tariff.baseRate)
  const serviceFee = Number(tariff.serviceFee)
  return consumptionCost + serviceFee
}
