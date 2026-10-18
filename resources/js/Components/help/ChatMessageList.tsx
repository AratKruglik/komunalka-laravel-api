import { useEffect, useRef } from 'react'
import { Sparkles } from 'lucide-react'
import { MessageBubble } from './MessageBubble'
import type { AssistantMessage, HelpApprovalDecision } from '@/types'

export interface ChatMessageListProps {
  messages: AssistantMessage[]
  isThinking: boolean
  thinkingLabel?: string | null
  activeApprovalId?: string | null
  approvalErrors?: Record<string, string>
  onDecide?: (approvalId: string, decision: HelpApprovalDecision) => void
  size?: 'default' | 'compact'
}

export function ChatMessageList({
  messages,
  isThinking,
  thinkingLabel = null,
  activeApprovalId = null,
  approvalErrors,
  onDecide,
  size = 'default',
}: ChatMessageListProps) {
  const containerRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    const container = containerRef.current
    if (!container) {
      return
    }
    container.scrollTop = container.scrollHeight
  }, [messages, isThinking])

  return (
    <div ref={containerRef} className="min-h-0 flex-1 space-y-3 overflow-y-auto">
      {messages.map((message) => (
        <MessageBubble
          key={message.id}
          message={message}
          size={size}
          isApprovalActive={message.approval !== undefined && message.approval.id === activeApprovalId}
          approvalErrors={approvalErrors}
          onDecide={onDecide}
        />
      ))}
      {isThinking ? (
        <div className="flex w-full items-start gap-3" role="status" aria-live="polite">
          <span className={`flex shrink-0 items-center justify-center rounded-full bg-primary text-on-primary ${size === 'compact' ? 'h-7 w-7' : 'h-8 w-8'}`}>
            <Sparkles className={size === 'compact' ? 'h-3.5 w-3.5' : 'h-4 w-4'} />
          </span>
          <div className="flex items-center gap-2 rounded-2xl rounded-tl-[5px] border border-line bg-raised px-4 py-4">
            <span className="flex gap-1">
              <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-muted [animation-delay:-0.3s]" />
              <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-muted [animation-delay:-0.15s]" />
              <span className="h-1.5 w-1.5 animate-bounce rounded-full bg-muted" />
            </span>
            {thinkingLabel ? <span className="text-xs text-subtext">{thinkingLabel}</span> : null}
          </div>
        </div>
      ) : null}
    </div>
  )
}
