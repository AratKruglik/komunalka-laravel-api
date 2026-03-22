import type { Address, Meter, Reading, Provider } from '@/types/entities'
import type { MeterType } from '@/constants/meterTypes'
import { METER_TYPE_TO_SERVICE_LABEL, UTILITY_TYPE_ID_TO_METER_TYPE } from '@/types/entities'
import { SERVICE_CONFIG } from '@/constants/services'
import type { LucideIcon } from 'lucide-react'
import { getPrimaryTariff } from '@/utils/providerTariffs'

function getMeterType(meter: Meter): MeterType {
  return UTILITY_TYPE_ID_TO_METER_TYPE[meter.utilityType.id] ?? 'electricity'
}

export interface ServiceDataViewModel {
  readonly id: number
  readonly name: string
  readonly icon: LucideIcon
  readonly iconBg: string
  readonly iconColor: string
  readonly cost: number
  readonly consumption: number
  readonly unit: string
  readonly rate: number
  readonly change: number
}

export interface ReadingViewModel {
  readonly id: number
  readonly serviceId: number
  readonly serviceName: string
  readonly serviceIcon: LucideIcon
  readonly serviceIconBg: string
  readonly serviceIconColor: string
  readonly date: string
  readonly value: number
  readonly unit: string
  readonly difference: number
}

export interface PaymentReminderViewModel {
  readonly id: number
  readonly serviceId: number
  readonly type: MeterType
  readonly serviceName: string
  readonly amount: number
  readonly dueDate: string
  readonly daysUntilDue: number
  readonly urgency: 'high' | 'medium' | 'low'
}

export interface ChartDataPointViewModel {
  readonly month: string
  readonly electricity?: number
  readonly gas?: number
  readonly water?: number
  readonly hotWater?: number
  readonly coldWater?: number
  readonly heating?: number
}

export interface ExpenseDistributionItemViewModel {
  readonly name: string
  readonly value: number
  readonly color: string
  readonly [key: string]: string | number
}

export type PeriodFilter = '3months' | '6months' | '1year'

export type ExpenseDistributionByPeriod = Record<
  PeriodFilter,
  readonly ExpenseDistributionItemViewModel[]
>

export interface DashboardAddressOptionViewModel {
  readonly id: number
  readonly label: string
  readonly description?: string
}

export function toServiceDataViewModel(
  meter: Meter,
  provider: Provider,
  latestReading: Reading | undefined,
  previousReading: Reading | undefined,
): ServiceDataViewModel {
  const meterType = getMeterType(meter)
  const config = SERVICE_CONFIG[meterType]
  const consumption = latestReading?.consumption || 0
  const primaryTariff = getPrimaryTariff(provider)
  const cost = consumption * (primaryTariff?.price ?? 0)

  let change = 0
  if (previousReading && previousReading.consumption) {
    const currentConsumption = latestReading?.consumption || 0
    const previousConsumption = previousReading.consumption
    change = ((currentConsumption - previousConsumption) / previousConsumption) * 100
  }

  return {
    id: meter.id,
    name: METER_TYPE_TO_SERVICE_LABEL[meterType],
    icon: config.icon,
    iconBg: config.iconBg,
    iconColor: config.iconColor,
    cost: Math.round(cost * 100) / 100,
    consumption,
    unit: provider.unitLabel.split('/')[1] || 'од',
    rate: primaryTariff?.price ?? 0,
    change: Math.round(change),
  }
}

export function toReadingViewModel(
  reading: Reading,
  meter: Meter,
  provider: Provider,
): ReadingViewModel {
  const meterType = getMeterType(meter)
  const config = SERVICE_CONFIG[meterType]

  return {
    id: reading.id,
    serviceId: meter.id,
    serviceName: METER_TYPE_TO_SERVICE_LABEL[meterType],
    serviceIcon: config.icon,
    serviceIconBg: config.iconBg,
    serviceIconColor: config.iconColor,
    date: reading.readingDate,
    value: reading.readingValue,
    unit: provider.unitLabel.split('/')[1] || 'од',
    difference: reading.consumption || 0,
  }
}

export function toPaymentReminderViewModel(
  provider: Provider,
  meter: Meter,
  latestReading: Reading | undefined,
  dueDate: string,
): PaymentReminderViewModel {
  const consumption = latestReading?.consumption || 0
  const primaryTariff = getPrimaryTariff(provider)
  const amount = consumption * (primaryTariff?.price ?? 0)

  const today = new Date()
  const dueDateObj = new Date(dueDate)
  const daysUntilDue = Math.ceil(
    (dueDateObj.getTime() - today.getTime()) / (1000 * 60 * 60 * 24),
  )

  let urgency: 'high' | 'medium' | 'low'
  if (daysUntilDue <= 5) {
    urgency = 'high'
  } else if (daysUntilDue <= 10) {
    urgency = 'medium'
  } else {
    urgency = 'low'
  }

  const meterType = getMeterType(meter)
  return {
    id: meter.id,
    serviceId: provider.id,
    type: meterType,
    serviceName: METER_TYPE_TO_SERVICE_LABEL[meterType],
    amount: Math.round(amount * 100) / 100,
    dueDate,
    daysUntilDue,
    urgency,
  }
}

export function toChartDataViewModel(
  readings: readonly Reading[],
  meters: readonly Meter[],
): readonly ChartDataPointViewModel[] {
  const monthsMap = new Map<string, Map<MeterType, number>>()

  readings.forEach((reading) => {
    const meter = meters.find((m) => m.id === reading.meter.id)
    if (!meter) return

    const meterType = getMeterType(meter)
    const date = new Date(reading.readingDate)
    const monthKey = date.toLocaleString('uk-UA', { month: 'short' })

    if (!monthsMap.has(monthKey)) {
      monthsMap.set(monthKey, new Map())
    }

    const monthData = monthsMap.get(monthKey)!
    const currentValue = monthData.get(meterType) || 0
    monthData.set(meterType, currentValue + (reading.consumption || 0))
  })

  type MutableDataPoint = {
    month: string
    electricity?: number
    gas?: number
    water?: number
    hotWater?: number
    coldWater?: number
    heating?: number
  }

  const chartData: ChartDataPointViewModel[] = []
  monthsMap.forEach((monthData, month) => {
    const dataPoint: MutableDataPoint = { month }

    monthData.forEach((value, meterType) => {
      switch (meterType) {
        case 'electricity':
          dataPoint.electricity = value
          break
        case 'gas':
          dataPoint.gas = value
          break
        case 'coldWater':
          dataPoint.coldWater = value
          dataPoint.water = (dataPoint.water || 0) + value
          break
        case 'hotWater':
          dataPoint.hotWater = value
          dataPoint.water = (dataPoint.water || 0) + value
          break
        case 'heat':
          dataPoint.heating = value
          break
      }
    })

    chartData.push(dataPoint as ChartDataPointViewModel)
  })

  return chartData
}

export function toDashboardAddressOptionViewModel(
  address: Address,
): DashboardAddressOptionViewModel {
  const apartment = address.apartmentNumber ? `, кв. ${address.apartmentNumber}` : ''
  const label = `${address.street}, ${address.buildingNumber}${apartment}`
  const description = `м. ${address.city}, ${address.region.name}`

  return {
    id: address.id,
    label,
    description,
  }
}

export function toExpenseDistributionViewModel(
  readings: readonly Reading[],
  meters: readonly Meter[],
  providers: readonly Provider[],
  period: PeriodFilter,
): readonly ExpenseDistributionItemViewModel[] {
  const monthsCount = period === '1year' ? 12 : period === '6months' ? 6 : 3

  const now = new Date()
  const startDate = new Date(now.getFullYear(), now.getMonth() - monthsCount, 1)

  const filteredReadings = readings.filter((r) => new Date(r.readingDate) >= startDate)

  const expensesByType = new Map<MeterType, number>()

  filteredReadings.forEach((reading) => {
    const meter = meters.find((m) => m.id === reading.meter.id)
    if (!meter) return

    const meterType = getMeterType(meter)
    const provider = providers.find((p) => p.id === meter.serviceProvider?.id)
    if (!provider) return

    const primaryTariff = getPrimaryTariff(provider)
    const cost = (reading.consumption || 0) * (primaryTariff?.price ?? 0)
    const currentValue = expensesByType.get(meterType) || 0
    expensesByType.set(meterType, currentValue + cost)
  })

  const distribution: ExpenseDistributionItemViewModel[] = []

  expensesByType.forEach((value, meterType) => {
    const config = SERVICE_CONFIG[meterType]
    distribution.push({
      name: METER_TYPE_TO_SERVICE_LABEL[meterType],
      value: Math.round(value * 100) / 100,
      color: config.chartColor,
    })
  })

  return distribution
}
