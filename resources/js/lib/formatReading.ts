const numberFormatter = new Intl.NumberFormat('uk-UA', { maximumFractionDigits: 3 })
const dateFormatter = new Intl.DateTimeFormat('uk-UA', { day: '2-digit', month: '2-digit', year: 'numeric' })

const ISO_DATE_PATTERN = /^(\d{4})-(\d{2})-(\d{2})$/

export function formatReadingNumber(value: number, unit?: string): string {
  const formatted = numberFormatter.format(value)
  return unit ? `${formatted} ${unit}` : formatted
}

function parseIsoDate(isoDate: string): Date | null {
  const match = ISO_DATE_PATTERN.exec(isoDate)
  if (!match) {
    return null
  }
  return new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]))
}

export function formatReadingDate(isoDate: string): string {
  const date = parseIsoDate(isoDate)
  return date ? dateFormatter.format(date) : isoDate
}

export function todayIsoDate(): string {
  const now = new Date()
  const month = String(now.getMonth() + 1).padStart(2, '0')
  const day = String(now.getDate()).padStart(2, '0')
  return `${now.getFullYear()}-${month}-${day}`
}

export function isValidReadingDate(isoDate: string): boolean {
  return parseIsoDate(isoDate) !== null && isoDate <= todayIsoDate()
}

export function parseReadingValue(raw: string): number | null {
  const normalized = raw.trim().replace(',', '.')
  if (normalized === '') {
    return null
  }
  const parsed = Number(normalized)
  return Number.isFinite(parsed) && parsed >= 0 ? parsed : null
}
