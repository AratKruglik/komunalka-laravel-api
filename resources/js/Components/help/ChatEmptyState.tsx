import { Sparkles } from 'lucide-react'
import { tv } from 'tailwind-variants'
import { ASSISTANT_DESCRIPTION, ASSISTANT_NAME } from '@/constants/assistant'

const avatar = tv({
  base: 'flex shrink-0 items-center justify-center rounded-full bg-primary text-on-primary',
  variants: {
    size: {
      default: 'h-20 w-20',
      compact: 'h-16 w-16',
    },
  },
})

const name = tv({
  base: 'font-bold text-foreground',
  variants: {
    size: {
      default: 'text-2xl',
      compact: 'text-xl',
    },
  },
})

export interface ChatEmptyStateProps {
  size?: 'default' | 'compact'
}

export function ChatEmptyState({ size = 'default' }: ChatEmptyStateProps) {
  return (
    <div className="flex flex-col items-center gap-3 px-4 py-6 text-center">
      <span className={avatar({ size })}>
        <Sparkles className={size === 'compact' ? 'h-8 w-8' : 'h-10 w-10'} />
      </span>
      <div className="space-y-1.5">
        <p className={name({ size })}>{ASSISTANT_NAME}</p>
        <p className="mx-auto max-w-xs text-sm leading-relaxed text-subtext">{ASSISTANT_DESCRIPTION}</p>
      </div>
    </div>
  )
}
