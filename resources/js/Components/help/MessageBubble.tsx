import { useState } from 'react'
import { ImageIcon, Sparkles } from 'lucide-react'
import { tv } from 'tailwind-variants'
import { MeterApprovalCard } from './MeterApprovalCard'
import { PropertySetupApprovalCard } from './PropertySetupApprovalCard'
import { ReadingResultNote } from './ReadingResultNote'
import { SourceChip } from './SourceChip'
import { AI_GENERATED_LABEL } from '@/constants/assistant'
import type { AssistantMessage, HelpApprovalDecision } from '@/types'

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
    size: {
      default: 'max-w-[75%]',
      compact: 'max-w-[85%]',
    },
  },
})

const text = tv({
  base: 'whitespace-pre-wrap break-words text-sm leading-relaxed',
  variants: {
    size: {
      default: '',
      compact: 'text-[13px]',
    },
  },
})

export interface MessageBubbleProps {
  message: AssistantMessage
  size?: 'default' | 'compact'
  isApprovalActive?: boolean
  approvalErrors?: Record<string, string>
  onDecide?: (approvalId: string, decision: HelpApprovalDecision) => void
}

interface MessagePhotoProps {
  photo: NonNullable<AssistantMessage['photo']>
}

function MessagePhoto({ photo }: MessagePhotoProps) {
  const [hasPreviewFailed, setHasPreviewFailed] = useState(false)

  if (photo.url && !hasPreviewFailed) {
    return (
      <img
        src={photo.url}
        alt={photo.name}
        onError={() => setHasPreviewFailed(true)}
        className="max-h-40 max-w-full rounded-2xl border border-line object-cover"
      />
    )
  }

  return (
    <span className="inline-flex max-w-full items-center gap-2 rounded-2xl bg-primary px-3 py-2 text-xs text-on-primary">
      <ImageIcon className="h-4 w-4 shrink-0" />
      <span className="truncate">{photo.name}</span>
    </span>
  )
}

export function MessageBubble({
  message,
  size = 'default',
  isApprovalActive = false,
  approvalErrors = {},
  onDecide,
}: MessageBubbleProps) {
  const isAssistant = message.role === 'assistant'
  const result = message.readingResult
  const isResultMessageDuplicate = result !== undefined && result.message === message.text

  return (
    <div className={messageRow({ role: message.role })}>
      {isAssistant ? (
        <span className={avatar({ size })}>
          <Sparkles className={size === 'compact' ? 'h-3.5 w-3.5' : 'h-4 w-4'} />
        </span>
      ) : null}
      <div className={messageCol({ role: message.role, size })}>
        {isAssistant ? (
          <div className="overflow-hidden rounded-2xl border border-line bg-raised">
            <p className={`${text({ size })} px-4 py-2.5 text-foreground`}>{message.text}</p>
            {result ? (
              <div className="px-4 pb-2.5">
                <ReadingResultNote result={result} hideMessage={isResultMessageDuplicate} />
              </div>
            ) : null}
            {message.setupResult && message.setupResult.message !== message.text ? (
              <p className="px-4 pb-2.5 text-xs text-subtext">{message.setupResult.message}</p>
            ) : null}
            {message.approval && message.approvalStatus && onDecide ? (
              message.approval.tool === 'create_property_setup' ? (
                <PropertySetupApprovalCard approval={message.approval} status={message.approvalStatus}
                  isActive={isApprovalActive} errors={approvalErrors} onDecide={onDecide} />
              ) : (
                <MeterApprovalCard approval={message.approval} status={message.approvalStatus}
                  isActive={isApprovalActive} errors={approvalErrors} onDecide={onDecide} size={size} />
              )
            ) : null}
            <div className="flex items-center gap-2 border-t border-line bg-surface px-4 py-2">
              <span className="inline-flex h-[18px] items-center rounded bg-canvas px-1.5 text-[10px] font-bold text-subtext">
                AI
              </span>
              <span className="text-xs font-medium text-subtext">{AI_GENERATED_LABEL}</span>
            </div>
          </div>
        ) : (
          <>
            {message.photo ? <MessagePhoto photo={message.photo} /> : null}
            {message.text !== '' ? (
              <div className={`${text({ size })} rounded-2xl bg-primary px-4 py-2.5 text-on-primary`}>
                {message.text}
              </div>
            ) : null}
          </>
        )}
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
