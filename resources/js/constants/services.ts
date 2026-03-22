import { Zap, Flame, Droplets, Thermometer, Waves } from 'lucide-react'
import type { LucideIcon } from 'lucide-react'
import type { MeterType } from './meterTypes'

export type ServiceType =
  | 'Електроенергія'
  | 'Газ'
  | 'Холодна вода'
  | 'Гаряча вода'
  | 'Водопостачання'
  | 'Опалення'

export interface ServiceConfig {
  icon: LucideIcon
  iconBg: string
  iconColor: string
  tagBg: string
  tagText: string
  chartColor: string
}

export const SERVICE_CONFIG: Record<MeterType, ServiceConfig> = {
  electricity: {
    icon: Zap,
    iconBg: 'bg-sky-100 dark:bg-sky-900/40',
    iconColor: 'text-sky-600 dark:text-sky-200',
    tagBg: 'bg-sky-100 dark:bg-sky-900/40',
    tagText: 'text-sky-800 dark:text-sky-100',
    chartColor: '#7DD3FC',
  },
  gas: {
    icon: Flame,
    iconBg: 'bg-amber-100 dark:bg-amber-900/40',
    iconColor: 'text-amber-600 dark:text-amber-200',
    tagBg: 'bg-amber-100 dark:bg-amber-900/40',
    tagText: 'text-amber-800 dark:text-amber-100',
    chartColor: '#FBBF24',
  },
  coldWater: {
    icon: Droplets,
    iconBg: 'bg-blue-100 dark:bg-blue-900/40',
    iconColor: 'text-blue-600 dark:text-blue-200',
    tagBg: 'bg-blue-100 dark:bg-blue-900/40',
    tagText: 'text-blue-800 dark:text-blue-100',
    chartColor: '#93C5FD',
  },
  hotWater: {
    icon: Thermometer,
    iconBg: 'bg-rose-100 dark:bg-rose-900/40',
    iconColor: 'text-rose-600 dark:text-rose-200',
    tagBg: 'bg-rose-100 dark:bg-rose-900/40',
    tagText: 'text-rose-800 dark:text-rose-100',
    chartColor: '#F9A8D4',
  },
  heat: {
    icon: Waves,
    iconBg: 'bg-orange-100 dark:bg-orange-900/40',
    iconColor: 'text-orange-600 dark:text-orange-200',
    tagBg: 'bg-orange-100 dark:bg-orange-900/40',
    tagText: 'text-orange-800 dark:text-orange-100',
    chartColor: '#FDBA74',
  },
}

export function getServiceIcon(meterType: MeterType): LucideIcon | null {
  const config = SERVICE_CONFIG[meterType]
  return config?.icon ?? null
}

export function getServiceIconClasses(meterType: MeterType): {
  iconBg: string
  iconColor: string
} {
  const config = SERVICE_CONFIG[meterType]
  return config
    ? { iconBg: config.iconBg, iconColor: config.iconColor }
    : { iconBg: 'bg-gray-100', iconColor: 'text-gray-600' }
}

export function getServiceTagClasses(meterType: MeterType): string {
  const config = SERVICE_CONFIG[meterType]
  return config ? `${config.tagBg} ${config.tagText}` : 'bg-gray-100 text-gray-800'
}
