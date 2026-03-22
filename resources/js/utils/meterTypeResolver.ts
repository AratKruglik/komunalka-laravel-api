import type { MeterType } from '@/constants/meterTypes'
import type { Meter } from '@/types/entities'
import { UTILITY_TYPE_ID_TO_METER_TYPE } from '@/types/entities'

const _UTILITY_SLUG_TO_METER_TYPE: Record<string, MeterType> = {
  electricity: 'electricity',
  gas: 'gas',
  cold_water: 'coldWater',
  coldwater: 'coldWater',
  hot_water: 'hotWater',
  hotwater: 'hotWater',
  heat: 'heat',
  heating: 'heat',
}

export function getMeterTypeFromMeter(meter: Meter): MeterType {
  const byId = UTILITY_TYPE_ID_TO_METER_TYPE[meter.utilityType.id]
  if (byId) return byId

  const bySlug = _UTILITY_SLUG_TO_METER_TYPE[meter.utilityType.slug]
  if (bySlug) return bySlug

  return 'electricity'
}
