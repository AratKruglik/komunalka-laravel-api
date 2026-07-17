import { useEffect, useRef, useState } from 'react'
import { Link } from '@inertiajs/react'
import { MessageCircle, RotateCcw, Sparkles, X } from 'lucide-react'
import { Button, Card } from '@/Components/ui'
import { useAssistantChat } from '@/hooks/useAssistantChat'
import {
  ASSISTANT_NAME,
  WIDGET_FOOTER_LINK_LABEL,
  WIDGET_GREETING,
  WIDGET_INPUT_PLACEHOLDER,
  WIDGET_QUICK_CHIPS,
} from '@/constants/assistant'
import { ChatEmptyState } from './ChatEmptyState'
import { ChatInput } from './ChatInput'
import { ChatMessageList } from './ChatMessageList'
import { SuggestedChips } from './SuggestedChips'

export function HelpChatWidget() {
  const [isOpen, setIsOpen] = useState(false)
  const [draft, setDraft] = useState('')
  const { messages, isThinking, send, reset } = useAssistantChat(WIDGET_GREETING)
  const fabRef = useRef<HTMLButtonElement>(null)
  const textareaRef = useRef<HTMLTextAreaElement>(null)

  const hasConversationStarted = messages.some((message) => message.role === 'user')

  const closePopup = () => {
    setIsOpen(false)
    fabRef.current?.focus()
  }

  const handleReset = () => {
    reset()
    setDraft('')
    textareaRef.current?.focus()
  }

  const handleSend = (question: string) => {
    send(question)
    setDraft('')
  }

  useEffect(() => {
    if (!isOpen) {
      return
    }

    textareaRef.current?.focus()

    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        closePopup()
      }
    }

    window.addEventListener('keydown', handleKeyDown)
    return () => {
      window.removeEventListener('keydown', handleKeyDown)
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isOpen])

  return (
    <>
      <Button
        ref={fabRef}
        type="button"
        variant="primary"
        size="icon"
        className="fixed bottom-6 right-6 z-50 h-14 w-14 shadow-lg"
        onClick={() => setIsOpen((previous) => !previous)}
        aria-label={isOpen ? 'Закрити чат допомоги' : 'Відкрити чат допомоги'}
        aria-expanded={isOpen}
      >
        <MessageCircle className="h-6 w-6" />
      </Button>

      {isOpen ? (
        <Card className="fixed bottom-24 right-6 z-50 flex h-[540px] w-[calc(100vw-2rem)] flex-col sm:w-96">
          <div className="flex items-center gap-3 bg-primary py-4 pl-[18px] pr-3 text-on-primary">
            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-black/10">
              <Sparkles className="h-[21px] w-[21px]" />
            </span>
            <p className="min-w-0 flex-1 truncate text-base font-bold">{ASSISTANT_NAME}</p>
            <button
              type="button"
              onClick={handleReset}
              className="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-black/[0.08] transition-colors hover:bg-black/[0.16]"
              aria-label="Почати нову розмову"
            >
              <RotateCcw className="h-[17px] w-[17px]" />
            </button>
            <button
              type="button"
              onClick={closePopup}
              className="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-black/[0.08] transition-colors hover:bg-black/[0.16]"
              aria-label="Закрити чат допомоги"
            >
              <X className="h-[17px] w-[17px]" />
            </button>
          </div>

          <div className="flex flex-1 flex-col gap-3 overflow-hidden px-4 py-3">
            {!hasConversationStarted ? <ChatEmptyState size="compact" /> : null}
            <ChatMessageList messages={messages} isThinking={isThinking} size="compact" />
            <SuggestedChips items={WIDGET_QUICK_CHIPS} onSelect={handleSend} disabled={isThinking} />
            <ChatInput
              ref={textareaRef}
              value={draft}
              onChange={setDraft}
              onSend={() => handleSend(draft)}
              disabled={isThinking}
              placeholder={WIDGET_INPUT_PLACEHOLDER}
            />
          </div>

          <div className="border-t border-line bg-canvas px-4 py-2.5">
            <Link
              href={route('help.index')}
              onClick={closePopup}
              className="text-xs font-medium text-primary hover:underline"
            >
              {WIDGET_FOOTER_LINK_LABEL}
            </Link>
          </div>
        </Card>
      ) : null}
    </>
  )
}
