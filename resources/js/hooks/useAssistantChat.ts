import { useEffect, useRef, useState } from 'react'
import { askAssistant, decideApproval, getHelpState, toAssistantRequestError } from '@/lib/assistantApi'
import type { AssistantRequestError } from '@/lib/assistantApi'
import { THINKING_PHOTO_LABEL, THINKING_SAVE_LABEL } from '@/constants/assistant'
import type {
  ApprovalStatus,
  AskAssistantResponse,
  AssistantMessage,
  HelpApprovalDecision,
} from '@/types'

export interface SendInput {
  question: string
  photo?: File | null
}

interface RequestOptions {
  thinkingLabel: string | null
  hasPhoto: boolean
  onSuccess: (data: AskAssistantResponse) => void
  onFailure: (error: AssistantRequestError) => void
}

const FORBIDDEN_STATUS = 403
const VALIDATION_STATUS = 422
const STALE_STATUS = 409
const CONVERSATION_STORAGE_KEY = 'hatkobot-conversation-id'

function createMessage(role: AssistantMessage['role'], text: string, extra: Partial<AssistantMessage> = {}): AssistantMessage {
  return {
    id: crypto.randomUUID(),
    role,
    text,
    ...extra,
  }
}

function createReplyMessage(data: AskAssistantResponse): AssistantMessage {
  return createMessage('assistant', data.text, {
    sources: data.sources,
    approval: data.pending_approval ?? undefined,
    approvalStatus: data.pending_approval ? 'pending' : undefined,
    readingResult: data.reading_result ?? undefined,
    setupResult: data.setup_result ?? undefined,
  })
}

function setApprovalStatus(messages: AssistantMessage[], approvalId: string, status: ApprovalStatus): AssistantMessage[] {
  return messages.map((message) =>
    message.approval?.id === approvalId ? { ...message, approvalStatus: status } : message,
  )
}

function markOpenApprovalsStale(messages: AssistantMessage[]): AssistantMessage[] {
  return messages.map((message) =>
    message.approvalStatus === 'pending' || message.approvalStatus === 'submitting'
      ? { ...message, approvalStatus: 'stale' }
      : message,
  )
}

function findActiveApprovalId(messages: AssistantMessage[]): string | null {
  const active = [...messages].reverse().find((message) => message.approvalStatus === 'pending' || message.approvalStatus === 'submitting')
  return active?.approval?.id ?? null
}

function resolveDecisionLabel(decision: HelpApprovalDecision): string | null {
  return decision.action === 'reject' ? null : THINKING_SAVE_LABEL
}

export function useAssistantChat(initialGreeting?: string) {
  const createInitialMessages = (): AssistantMessage[] =>
    initialGreeting ? [createMessage('assistant', initialGreeting)] : []

  const [messages, setMessages] = useState<AssistantMessage[]>(createInitialMessages)
  const [isThinking, setIsThinking] = useState(false)
  const [thinkingLabel, setThinkingLabel] = useState<string | null>(null)
  const [conversationId, setConversationId] = useState<string | null>(() =>
    typeof window === 'undefined' ? null : window.localStorage.getItem(CONVERSATION_STORAGE_KEY),
  )
  const [approvalErrors, setApprovalErrors] = useState<Record<string, string>>({})
  const activeRequest = useRef<AbortController | null>(null)
  const requestVersion = useRef(0)
  const objectUrls = useRef<Set<string>>(new Set())

  const releaseObjectUrls = (): void => {
    objectUrls.current.forEach((url) => URL.revokeObjectURL(url))
    objectUrls.current.clear()
  }

  useEffect(() => {
    const urls = objectUrls.current
    return () => {
      activeRequest.current?.abort()
      urls.forEach((url) => URL.revokeObjectURL(url))
      urls.clear()
    }
  }, [])

  useEffect(() => {
    if (conversationId) window.localStorage.setItem(CONVERSATION_STORAGE_KEY, conversationId)
    else window.localStorage.removeItem(CONVERSATION_STORAGE_KEY)
  }, [conversationId])

  useEffect(() => {
    const saved = window.localStorage.getItem(CONVERSATION_STORAGE_KEY)
    if (!saved) return
    const controller = new AbortController()
    getHelpState(saved, controller.signal)
      .then((state) => {
        if (state.pending_approval && requestVersion.current === 0) {
          setMessages((previous) => [...previous, createReplyMessage({ ...state, text: 'Відновлено пропозицію для підтвердження.' })])
        }
      })
      .catch((error: unknown) => {
        if (controller.signal.aborted) return
        if (toAssistantRequestError(error, false).status === FORBIDDEN_STATUS) setConversationId(null)
      })
    return () => controller.abort()
  }, [])

  const appendAssistantText = (text: string): void => {
    setMessages((previous) => [...previous, createMessage('assistant', text)])
  }

  const execute = (request: (signal: AbortSignal) => Promise<AskAssistantResponse>, options: RequestOptions): void => {
    const controller = new AbortController()
    const version = ++requestVersion.current

    activeRequest.current = controller
    setIsThinking(true)
    setThinkingLabel(options.thinkingLabel)

    request(controller.signal)
      .then((data) => {
        if (version !== requestVersion.current) {
          return
        }
        setConversationId((previous) => data.conversation_id ?? previous)
        options.onSuccess(data)
      })
      .catch((error: unknown) => {
        if (controller.signal.aborted || version !== requestVersion.current) {
          return
        }
        options.onFailure(toAssistantRequestError(error, options.hasPhoto))
      })
      .finally(() => {
        if (version === requestVersion.current) {
          activeRequest.current = null
          setIsThinking(false)
          setThinkingLabel(null)
        }
      })
  }

  const createUserMessage = (question: string, photo: File | null): AssistantMessage => {
    if (!photo) {
      return createMessage('user', question)
    }

    const url = URL.createObjectURL(photo)
    objectUrls.current.add(url)
    return createMessage('user', question, { photo: { url, name: photo.name } })
  }

  const handleSendFailure = (error: AssistantRequestError): void => {
    if (error.status === FORBIDDEN_STATUS) {
      setConversationId(null)
    }
    appendAssistantText(error.message)
  }

  const send = ({ question, photo = null }: SendInput): boolean => {
    const trimmedQuestion = question.trim()
    if ((trimmedQuestion === '' && !photo) || isThinking || activeRequest.current) {
      return false
    }

    setApprovalErrors({})
    setMessages((previous) => [...markOpenApprovalsStale(previous), createUserMessage(trimmedQuestion, photo)])

    execute((signal) => askAssistant({ question: trimmedQuestion, photo, conversationId }, signal), {
      thinkingLabel: photo ? THINKING_PHOTO_LABEL : null,
      hasPhoto: photo !== null,
      onSuccess: (data) => setMessages((previous) => [...previous, createReplyMessage(data)]),
      onFailure: handleSendFailure,
    })

    return true
  }

  const handleDecisionFailure = (approvalId: string, error: AssistantRequestError): void => {
    if (error.status === VALIDATION_STATUS) {
      setApprovalErrors(error.fieldErrors)
      setMessages((previous) => setApprovalStatus(previous, approvalId, 'pending'))
      return
    }

    const isStale = error.status === STALE_STATUS || error.status === FORBIDDEN_STATUS
    if (error.status === FORBIDDEN_STATUS) {
      setConversationId(null)
    }
    setMessages((previous) => [
      ...setApprovalStatus(previous, approvalId, isStale ? 'stale' : 'pending'),
      createMessage('assistant', error.message),
    ])
  }

  const decide = (approvalId: string, decision: HelpApprovalDecision): void => {
    if (!conversationId || isThinking || activeRequest.current) {
      return
    }

    setApprovalErrors({})
    setMessages((previous) => setApprovalStatus(previous, approvalId, 'submitting'))

    const approval = messages.find((message) => message.approval?.id === approvalId)?.approval
    execute((signal) => decideApproval({ conversationId, approvalId, version: approval?.version, decision }, signal), {
      thinkingLabel: resolveDecisionLabel(decision),
      hasPhoto: false,
      onSuccess: (data) =>
        setMessages((previous) => [...setApprovalStatus(previous, approvalId, 'resolved'), createReplyMessage(data)]),
      onFailure: (error) => handleDecisionFailure(approvalId, error),
    })
  }

  const reset = (): void => {
    requestVersion.current += 1
    activeRequest.current?.abort()
    activeRequest.current = null
    releaseObjectUrls()
    setConversationId(null)
    setIsThinking(false)
    setThinkingLabel(null)
    setApprovalErrors({})
    setMessages(createInitialMessages())
  }

  return {
    messages,
    isThinking,
    thinkingLabel,
    activeApprovalId: findActiveApprovalId(messages),
    approvalErrors,
    send,
    decide,
    reset,
  }
}
