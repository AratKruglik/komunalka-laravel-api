import { forwardRef, useRef, useState } from 'react'
import type { ChangeEvent, KeyboardEvent } from 'react'
import { Paperclip, SendHorizontal } from 'lucide-react'
import { FormMessage } from '@/Components/ui'
import { validateAssistantPhoto } from '@/lib/assistantApi'
import {
  ATTACH_PHOTO_TITLE,
  PHOTO_FILE_INPUT_ACCEPT,
  PHOTO_PRIVACY_NOTICE,
  TEXT_PRIVACY_NOTICE,
} from '@/constants/assistant'
import { ChatPhotoAttachment } from './ChatPhotoAttachment'

export interface ChatInputProps {
  value: string
  onChange: (value: string) => void
  onSend: () => void
  photo?: File | null
  onPhotoChange?: (file: File | null) => void
  disabled?: boolean
  placeholder?: string
}

export const ChatInput = forwardRef<HTMLTextAreaElement, ChatInputProps>(function ChatInput(
  { value, onChange, onSend, photo = null, onPhotoChange, disabled = false, placeholder },
  ref,
) {
  const fileInputRef = useRef<HTMLInputElement>(null)
  const [photoError, setPhotoError] = useState<string | null>(null)

  const handleKeyDown = (event: KeyboardEvent<HTMLTextAreaElement>) => {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault()
      onSend()
    }
  }

  const isSendDisabled = disabled || (value.trim() === '' && photo === null)

  const handleFileChange = (event: ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0] ?? null
    event.target.value = ''
    if (!file) {
      return
    }

    const error = validateAssistantPhoto(file)
    setPhotoError(error)
    if (!error) {
      onPhotoChange?.(file)
    }
  }

  const handleRemovePhoto = () => {
    setPhotoError(null)
    onPhotoChange?.(null)
  }

  return (
    <div className="space-y-1">
      {photo ? <ChatPhotoAttachment file={photo} onRemove={handleRemovePhoto} disabled={disabled} /> : null}
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
        {onPhotoChange ? (
          <input
            ref={fileInputRef}
            type="file"
            accept={PHOTO_FILE_INPUT_ACCEPT}
            onChange={handleFileChange}
            className="hidden"
            tabIndex={-1}
            aria-hidden="true"
          />
        ) : null}
        <button
          type="button"
          disabled={disabled || !onPhotoChange}
          onClick={() => fileInputRef.current?.click()}
          title={ATTACH_PHOTO_TITLE}
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
      {photoError ? <FormMessage variant="error" className="px-2 text-xs">{photoError}</FormMessage> : null}
      <p className="px-2 text-[11px] leading-relaxed text-muted">
        {photo ? PHOTO_PRIVACY_NOTICE : TEXT_PRIVACY_NOTICE}
      </p>
    </div>
  )
})
