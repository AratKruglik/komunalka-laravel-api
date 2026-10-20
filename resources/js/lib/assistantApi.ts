import {
  FALLBACK_ERROR_TEXT,
  FORBIDDEN_CONVERSATION_TEXT,
  PHOTO_ACCEPTED_EXTENSIONS,
  PHOTO_ACCEPTED_MIME_TYPES,
  PHOTO_FALLBACK_ERROR_TEXT,
  PHOTO_MAX_SIZE_BYTES,
  PHOTO_TOO_LARGE_TEXT,
  PHOTO_UNSUPPORTED_TEXT,
  RATE_LIMIT_ERROR_TEXT,
  STALE_APPROVAL_TEXT,
} from '@/constants/assistant'
import type { AskAssistantResponse, HelpApprovalDecision } from '@/types'

export class AssistantRequestError extends Error {
  readonly status: number
  readonly fieldErrors: Record<string, string>

  constructor(status: number, message: string, fieldErrors: Record<string, string> = {}) {
    super(message)
    this.name = 'AssistantRequestError'
    this.status = status
    this.fieldErrors = fieldErrors
  }
}

export interface AskAssistantInput {
  question: string
  photo: File | null
  conversationId: string | null
}

export interface DecideApprovalInput {
  conversationId: string
  approvalId: string
  version?: number
  decision: HelpApprovalDecision
}

function readXsrfToken(): string {
  const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)
  return decodeURIComponent(match?.[1] ?? '')
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return typeof value === 'object' && value !== null
}

async function readJsonBody(response: Response): Promise<unknown> {
  try {
    return await response.json()
  } catch {
    return null
  }
}

function extractFieldErrors(body: unknown): Record<string, string> {
  if (!isRecord(body) || !isRecord(body.errors)) {
    return {}
  }

  const fieldErrors: Record<string, string> = {}
  for (const [field, messages] of Object.entries(body.errors)) {
    const first = Array.isArray(messages) ? messages[0] : null
    if (typeof first === 'string') {
      fieldErrors[field] = first
    }
  }
  return fieldErrors
}

function pickValidationMessage(fieldErrors: Record<string, string>): string | null {
  const argumentMessages = Object.entries(fieldErrors)
    .filter(([field]) => field.startsWith('arguments'))
    .map(([, message]) => message)

  const ordered = [
    fieldErrors.photo,
    fieldErrors.question,
    ...argumentMessages,
    fieldErrors.approval_id,
    ...Object.values(fieldErrors),
  ]

  return ordered.find((message) => Boolean(message)) ?? null
}

function readMessage(body: unknown): string | null {
  return isRecord(body) && typeof body.message === 'string' && body.message !== '' ? body.message : null
}

function resolveErrorMessage(status: number, body: unknown, fieldErrors: Record<string, string>, hasPhoto: boolean): string {
  switch (status) {
    case 422:
      return pickValidationMessage(fieldErrors) ?? FALLBACK_ERROR_TEXT
    case 409:
      return readMessage(body) ?? STALE_APPROVAL_TEXT
    case 429:
      return RATE_LIMIT_ERROR_TEXT
    case 403:
      return FORBIDDEN_CONVERSATION_TEXT
    case 413:
      return PHOTO_TOO_LARGE_TEXT
    default:
      return hasPhoto ? PHOTO_FALLBACK_ERROR_TEXT : FALLBACK_ERROR_TEXT
  }
}

async function toRequestError(response: Response, hasPhoto: boolean): Promise<AssistantRequestError> {
  const body = await readJsonBody(response)
  const fieldErrors = extractFieldErrors(body)

  return new AssistantRequestError(response.status, resolveErrorMessage(response.status, body, fieldErrors, hasPhoto), fieldErrors)
}

export function toAssistantRequestError(error: unknown, hasPhoto: boolean): AssistantRequestError {
  if (error instanceof AssistantRequestError) {
    return error
  }
  return new AssistantRequestError(0, hasPhoto ? PHOTO_FALLBACK_ERROR_TEXT : FALLBACK_ERROR_TEXT)
}

async function postForReply(url: string, body: BodyInit, contentType: string | null, signal: AbortSignal, hasPhoto: boolean): Promise<AskAssistantResponse> {
  const headers: Record<string, string> = {
    Accept: 'application/json',
    'X-XSRF-TOKEN': readXsrfToken(),
  }
  if (contentType) {
    headers['Content-Type'] = contentType
  }

  const response = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers,
    body,
    signal,
  })

  if (!response.ok) {
    throw await toRequestError(response, hasPhoto)
  }

  return (await response.json()) as AskAssistantResponse
}

function buildAskBody(input: AskAssistantInput): { body: BodyInit; contentType: string | null } {
  if (input.photo) {
    const formData = new FormData()
    formData.append('photo', input.photo)
    if (input.question !== '') {
      formData.append('question', input.question)
    }
    if (input.conversationId) {
      formData.append('conversation_id', input.conversationId)
    }
    return { body: formData, contentType: null }
  }

  return {
    body: JSON.stringify({
      question: input.question,
      ...(input.conversationId ? { conversation_id: input.conversationId } : {}),
    }),
    contentType: 'application/json',
  }
}

export function askAssistant(input: AskAssistantInput, signal: AbortSignal): Promise<AskAssistantResponse> {
  const { body, contentType } = buildAskBody(input)
  return postForReply(route('help.ask'), body, contentType, signal, input.photo !== null)
}

export async function getHelpState(conversationId: string, signal: AbortSignal): Promise<AskAssistantResponse> {
  const response = await fetch(`${route('help.state')}?conversation_id=${encodeURIComponent(conversationId)}`, {
    method: 'GET',
    credentials: 'same-origin',
    headers: { Accept: 'application/json' },
    signal,
  })
  if (!response.ok) throw await toRequestError(response, false)
  return (await response.json()) as AskAssistantResponse
}

export function decideApproval(input: DecideApprovalInput, signal: AbortSignal): Promise<AskAssistantResponse> {
  const body = JSON.stringify({
    conversation_id: input.conversationId,
    approval_id: input.approvalId,
    ...(input.version !== undefined ? { version: input.version } : {}),
    action: input.decision.action,
    ...(input.decision.arguments ? { arguments: input.decision.arguments } : {}),
  })
  return postForReply(route('help.approve'), body, 'application/json', signal, false)
}

function fileExtension(name: string): string {
  const dotIndex = name.lastIndexOf('.')
  return dotIndex === -1 ? '' : name.slice(dotIndex + 1).toLowerCase()
}

export function validateAssistantPhoto(file: File): string | null {
  if (file.size > PHOTO_MAX_SIZE_BYTES) {
    return PHOTO_TOO_LARGE_TEXT
  }

  const hasKnownMime = PHOTO_ACCEPTED_MIME_TYPES.includes(file.type.toLowerCase())
  const hasKnownExtension = PHOTO_ACCEPTED_EXTENSIONS.includes(fileExtension(file.name))
  const isAccepted = file.type === '' ? hasKnownExtension : hasKnownMime

  return isAccepted ? null : PHOTO_UNSUPPORTED_TEXT
}
