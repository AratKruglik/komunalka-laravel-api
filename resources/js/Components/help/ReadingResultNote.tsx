import { Link } from '@inertiajs/react'
import { Alert, AlertDescription } from '@/Components/ui'
import { READING_RESULT_LABELS } from '@/constants/assistant'
import { formatReadingDate, formatReadingNumber } from '@/lib/formatReading'
import type { MeterReadingResult } from '@/types'

export interface ReadingResultNoteProps {
  result: MeterReadingResult
  hideMessage?: boolean
}

export function ReadingResultNote({ result, hideMessage = false }: ReadingResultNoteProps) {
  if (result.status === 'rejected') {
    return <p className="text-xs text-subtext">{READING_RESULT_LABELS.rejected}</p>
  }

  if (result.status === 'saved' && result.reading) {
    const { reading } = result
    return (
      <Alert variant="success" className="px-3 py-2">
        <AlertDescription className="space-y-1">
          <span className="block font-medium">{READING_RESULT_LABELS.savedTitle}</span>
          <span className="block text-xs">
            {formatReadingNumber(reading.reading_value)} · {formatReadingDate(reading.reading_date)} ·{' '}
            {READING_RESULT_LABELS.consumption} {formatReadingNumber(reading.consumption)}
          </span>
          <Link href={route('readings.index')} className="block text-xs font-medium text-primary hover:underline">
            {READING_RESULT_LABELS.viewReadings}
          </Link>
        </AlertDescription>
      </Alert>
    )
  }

  if (hideMessage) {
    return null
  }

  return (
    <Alert variant="warning" className="px-3 py-2">
      <AlertDescription>{result.message}</AlertDescription>
    </Alert>
  )
}
