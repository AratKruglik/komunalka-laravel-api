import type { LucideIcon } from 'lucide-react'
import { SERVICE_CONFIG } from './services'
import type { ServiceLabel } from '@/types/entities'

export type MeterType = 'electricity' | 'gas' | 'coldWater' | 'hotWater' | 'heat'

interface BaseMeterTypeConfig {
  value: MeterType
  title: string
  description: string
  serviceLabel: ServiceLabel
  unit: string
}

const baseMeterTypeConfigs: BaseMeterTypeConfig[] = [
  {
    value: 'electricity',
    title: 'Електролічильник',
    description: 'Для обліку споживання електроенергії',
    serviceLabel: 'Електроенергія',
    unit: 'кВт·год',
  },
  {
    value: 'gas',
    title: 'Газовий лічильник',
    description: 'Контроль споживання газу',
    serviceLabel: 'Газ',
    unit: 'м³',
  },
  {
    value: 'coldWater',
    title: 'Лічильник холодної води',
    description: 'Для холодного водопостачання',
    serviceLabel: 'Холодна вода',
    unit: 'м³',
  },
  {
    value: 'hotWater',
    title: 'Лічильник гарячої води',
    description: 'Для гарячого водопостачання',
    serviceLabel: 'Гаряча вода',
    unit: 'м³',
  },
  {
    value: 'heat',
    title: 'Лічильник тепла',
    description: 'Для систем опалення та теплопостачання',
    serviceLabel: 'Опалення',
    unit: 'Гкал',
  },
]

export interface MeterTypeOption {
  value: MeterType
  title: string
  description: string
  serviceLabel: ServiceLabel
  unit: string
  icon: LucideIcon
}

export const METER_TYPE_OPTIONS: MeterTypeOption[] = baseMeterTypeConfigs.map((config) => {
  const serviceConfig = SERVICE_CONFIG[config.value]

  return {
    ...config,
    icon: serviceConfig.icon,
  }
})

export const METER_TYPE_UNITS: Record<MeterType, string> = baseMeterTypeConfigs.reduce(
  (units, config) => {
    return { ...units, [config.value]: config.unit }
  },
  {} as Record<MeterType, string>,
)

export function getMeterTypeIcon(value: MeterType): LucideIcon | null {
  const option = METER_TYPE_OPTIONS.find((entry) => entry.value === value)
  return option?.icon ?? null
}
