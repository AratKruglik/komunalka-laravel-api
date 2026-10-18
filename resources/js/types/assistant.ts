export interface AssistantSource {
  label: string
  path: string
}

export type MeterApprovalUtilitySlug = 'cold-water' | 'hot-water' | 'electricity' | 'gas'

export interface MeterApprovalTariff {
  id: number
  name: string
}

export interface MeterReadingApproval {
  id: string
  tool: 'record_meter_reading'
  meter: {
    id: number
    name: string
    location: string | null
    serial_number: string | null
    utility_type: {
      slug: MeterApprovalUtilitySlug
      name: string
      unit: string
    }
    address: string
  }
  reading_value: number
  reading_date: string
  date_source: 'exif' | 'manual'
  exif_date: string | null
  tariff: MeterApprovalTariff | null
  tariff_options: MeterApprovalTariff[]
  previous: {
    value: number
    date: string | null
  }
  consumption: number
  expires_at: string
}

export type MeterReadingResultStatus = 'saved' | 'failed' | 'refused' | 'rejected'

export interface SavedMeterReading {
  id: number
  meter_id: number
  reading_value: number
  reading_date: string
  consumption: number
}

export interface MeterReadingResult {
  status: MeterReadingResultStatus
  message: string
  reading: SavedMeterReading | null
}

export interface AskAssistantResponse {
  text: string
  sources: AssistantSource[]
  conversation_id: string | null
  pending_approval: MeterReadingApproval | null
  reading_result: MeterReadingResult | null
}

export interface HelpApprovalDecision {
  action: 'approve' | 'reject' | 'edit'
  arguments?: {
    reading_value?: number
    reading_date?: string
    tariff_id?: number | null
  }
}

export type ApprovalStatus = 'pending' | 'submitting' | 'resolved' | 'stale'

export interface AssistantMessagePhoto {
  url: string | null
  name: string
}

export interface AssistantMessage {
  id: string
  role: 'user' | 'assistant'
  text: string
  sources?: AssistantSource[]
  photo?: AssistantMessagePhoto
  approval?: MeterReadingApproval
  approvalStatus?: ApprovalStatus
  readingResult?: MeterReadingResult
}

export interface TopicShortcut {
  label: string
  path: string
}
