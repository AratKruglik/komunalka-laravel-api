import type { Provider } from '@/types/entities'
import type { ApiServiceProvider, ApiTariff } from '@/types/api'

export const getPrimaryTariff = (provider: Provider) => {
  return provider.tariffs[0]
}

export const formatTariffLabel = (tariffPrice: number, unitLabel: string) => {
  return `${tariffPrice.toFixed(2)} ${unitLabel}`
}

export const getApiPrimaryTariff = (provider: ApiServiceProvider): ApiTariff | undefined => {
  return provider.tariffs[0]
}

export const formatApiTariffLabel = (tariff: ApiTariff, unit?: string): string => {
  const price = Number(tariff.baseRate).toFixed(2)
  const symbol = tariff.currency.symbol
  const displayUnit = unit ?? getUnitByUtilityTypeId(tariff.utilityType.id)
  return `${price} ${symbol}/${displayUnit}`
}

const getUnitByUtilityTypeId = (utilityTypeId: number): string => {
  const units: Record<number, string> = {
    1: 'м³',
    2: 'кВт·год',
    3: 'м³',
    4: 'м³',
    5: 'Гкал',
  }
  return units[utilityTypeId] ?? 'од'
}
