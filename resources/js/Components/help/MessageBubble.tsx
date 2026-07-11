import { Sparkles } from 'lucide-react'
import { tv } from 'tailwind-variants'
import { SourceChip } from './SourceChip'
import type { AssistantMessage } from '@/types'

const messageRow = tv({
  base: 'flex w-full items-start gap-3',
  variants: {
    role: {
      user: 'flex-row-reverse',
      assistant: '',
    },
  },
})

const avatar = tv({
  base: 'flex shrink-0 items-center justify-center rounded-full bg-primary text-on-primary',
  variants: {
    size: {
      default: 'h-8 w-8',
      compact: 'h-7 w-7',
    },
  },
})

const messageCol = tv({
  base: 'flex max-w-[85%] min-w-0 flex-col gap-2.5',
  variants: {
    role: {
      user: 'items-end',
      assistant: 'items-start',
    },
  },
})

const bubble = tv({
  base: 'whitespace-pre-wrap break-words rounded-2xl px-4 py-2.5 text-sm leading-relaxed',
  variants: {
    role: {
      user: 'rounded-tr-[5px] bg-primary text-on-primary',
      assistant: 'rounded-tl-[5px] border border-line bg-raised text-foreground',
    },
    size: {
      default: 'max-w-[75%]',
      compact: 'max-w-[85%] text-[13px]',
    },
  },
})

export interface MessageBubbleProps {
  message: AssistantMessage
  size?: 'default' | 'compact'
}

export function MessageBubble({ message, size = 'default' }: MessageBubbleProps) {
  const isAssistant = message.role === 'assistant'

  return (
    <div className={messageRow({ role: message.role })}>
      {isAssistant ? (
        <span className={avatar({ size })}>
          <Sparkles className={size === 'compact' ? 'h-3.5 w-3.5' : 'h-4 w-4'} />
        </span>
      ) : null}
      <div className={messageCol({ role: message.role })}>
        <div className={bubble({ role: message.role, size })}>{message.text}</div>
        {isAssistant && message.sources && message.sources.length > 0 ? (
          <div className="flex w-full flex-col gap-2">
            {message.sources.map((source) => (
              <SourceChip key={source.path} source={source} />
            ))}
          </div>
        ) : null}
      </div>
    </div>
  )
}
