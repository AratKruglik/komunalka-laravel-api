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
  version?: number
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

export interface PropertySetupApproval {
  id: string
  tool: 'create_property_setup'
  version: number
  expires_at: string
  address: Record<string, unknown> | null
  provider: Record<string, unknown> | null
  meter: Record<string, unknown> | null
  warnings: string[]
}

export type AssistantApproval = MeterReadingApproval | PropertySetupApproval

export interface PropertySetupResult {
  status: 'applied' | 'failed' | 'rejected'
  message: string
  address: { id: number } | null
  provider: { id: number } | null
  meter: { id: number } | null
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
  pending_approval: AssistantApproval | null
  reading_result: MeterReadingResult | null
  workflow?: 'meter_reading' | 'property_setup' | 'product_support' | 'clarify' | null
  setup_result?: PropertySetupResult | null
}

export interface HelpApprovalDecision {
  action: 'approve' | 'reject' | 'edit'
  arguments?: {
    reading_value?: number
    reading_date?: string
    tariff_id?: number | null
    address_id?: number | null
    address?: Record<string, string | number | boolean | null>
    provider?: Record<string, unknown>
    meter?: Record<string, string | number | boolean | null>
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
  approval?: AssistantApproval
  approvalStatus?: ApprovalStatus
  readingResult?: MeterReadingResult
  setupResult?: PropertySetupResult
}

export interface TopicShortcut {
  label: string
  path: string
}
