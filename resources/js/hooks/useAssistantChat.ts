import { useState } from 'react'
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
  const [messages, setMessages] = useState<AssistantMessage[]>(() =>
    initialGreeting ? [createMessage('assistant', initialGreeting)] : [],
  )
  const [isThinking, setIsThinking] = useState(false)

  const send = (question: string): void => {
    const trimmedQuestion = question.trim()
    if (trimmedQuestion === '' || isThinking) {
      return
    }

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
      body: JSON.stringify({ question: trimmedQuestion }),
    })
      .then(async (response) => {
        if (!response.ok) {
          throw new Error(`Request failed with status ${response.status}`)
        }
        const data = (await response.json()) as AskAssistantResponse
        setMessages((previous) => [
          ...previous,
          createMessage('assistant', data.text, data.sources),
        ])
      })
      .catch(() => {
        setMessages((previous) => [...previous, createMessage('assistant', FALLBACK_ERROR_TEXT)])
      })
      .finally(() => {
        setIsThinking(false)
      })
  }

  return { messages, isThinking, send }
}
