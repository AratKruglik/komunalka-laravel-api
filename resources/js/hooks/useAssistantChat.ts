import { useEffect, useRef, useState } from 'react'
import type { AskAssistantResponse, AssistantMessage } from '@/types'

const FALLBACK_ERROR_TEXT = 'Не вдалося отримати відповідь. Спробуйте ще раз.'

function readXsrfToken(): string {
  const match = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)
  return decodeURIComponent(match?.[1] ?? '')
}

function createMessage(role: AssistantMessage['role'], text: string, sources?: AssistantMessage['sources']): AssistantMessage {
  return {
    id: crypto.randomUUID(),
    role,
    text,
    sources,
  }
}

export function useAssistantChat(initialGreeting?: string) {
  const createInitialMessages = (): AssistantMessage[] =>
    initialGreeting ? [createMessage('assistant', initialGreeting)] : []

  const [messages, setMessages] = useState<AssistantMessage[]>(createInitialMessages)
  const [isThinking, setIsThinking] = useState(false)
  const [conversationId, setConversationId] = useState<string | null>(null)
  const activeRequest = useRef<AbortController | null>(null)
  const requestVersion = useRef(0)

  useEffect(() => {
    return () => activeRequest.current?.abort()
  }, [])

  const send = (question: string): void => {
    const trimmedQuestion = question.trim()
    if (trimmedQuestion === '' || isThinking || activeRequest.current) {
      return
    }

    const controller = new AbortController()
    const version = ++requestVersion.current

    activeRequest.current = controller
    setMessages((previous) => [...previous, createMessage('user', trimmedQuestion)])
    setIsThinking(true)

    fetch(route('help.ask'), {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-XSRF-TOKEN': readXsrfToken(),
      },
      body: JSON.stringify({
        question: trimmedQuestion,
        ...(conversationId ? { conversation_id: conversationId } : {}),
      }),
      signal: controller.signal,
    })
      .then(async (response) => {
        if (!response.ok) {
          throw new Error(`Request failed with status ${response.status}`)
        }
        const data = (await response.json()) as AskAssistantResponse

        if (version !== requestVersion.current) {
          return
        }

        setConversationId(data.conversation_id)
        setMessages((previous) => [
          ...previous,
          createMessage('assistant', data.text, data.sources),
        ])
      })
      .catch(() => {
        if (controller.signal.aborted || version !== requestVersion.current) {
          return
        }

        setMessages((previous) => [...previous, createMessage('assistant', FALLBACK_ERROR_TEXT)])
      })
      .finally(() => {
        if (version === requestVersion.current) {
          activeRequest.current = null
          setIsThinking(false)
        }
      })
  }

  const reset = (): void => {
    requestVersion.current += 1
    activeRequest.current?.abort()
    activeRequest.current = null
    setConversationId(null)
    setIsThinking(false)
    setMessages(createInitialMessages())
  }

  return { messages, isThinking, send, reset }
}
