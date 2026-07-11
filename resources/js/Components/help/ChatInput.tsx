import { forwardRef } from 'react'
import type { KeyboardEvent } from 'react'
import { Paperclip, SendHorizontal } from 'lucide-react'

export interface ChatInputProps {
  value: string
  onChange: (value: string) => void
  onSend: () => void
  disabled?: boolean
  placeholder?: string
}

export const ChatInput = forwardRef<HTMLTextAreaElement, ChatInputProps>(function ChatInput(
  { value, onChange, onSend, disabled = false, placeholder },
  ref,
) {
  const handleKeyDown = (event: KeyboardEvent<HTMLTextAreaElement>) => {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault()
      onSend()
    }
  }

  const isSendDisabled = disabled || value.trim() === ''

  return (
    <div className="flex items-end gap-1.5 rounded-2xl border border-line bg-raised py-1.5 pl-3.5 pr-1.5 focus-within:border-primary focus-within:ring-1 focus-within:ring-primary">
      <textarea
        ref={ref}
        value={value}
        onChange={(event) => onChange(event.target.value)}
        onKeyDown={handleKeyDown}
        placeholder={placeholder}
        disabled={disabled}
        rows={1}
        className="max-h-32 min-h-[42px] flex-1 resize-none border-none bg-transparent py-2.5 text-sm text-foreground placeholder:text-muted focus:outline-none focus:ring-0 disabled:cursor-not-allowed"
      />
      <button
        type="button"
        disabled={disabled}
        className="flex h-[38px] w-[38px] shrink-0 items-center justify-center rounded-full text-subtext transition-colors hover:text-foreground disabled:pointer-events-none disabled:opacity-60"
        aria-label="Додати вкладення"
      >
        <Paperclip className="h-[19px] w-[19px]" />
      </button>
      <button
        type="button"
        disabled={isSendDisabled}
        onClick={onSend}
        className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-primary text-on-primary transition-colors hover:bg-primary-dark disabled:pointer-events-none disabled:opacity-50"
        aria-label="Надіслати повідомлення"
      >
        <SendHorizontal className="h-[18px] w-[18px]" />
      </button>
    </div>
  )
})
