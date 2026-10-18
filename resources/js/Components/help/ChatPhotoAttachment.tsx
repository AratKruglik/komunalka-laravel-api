import { useEffect, useState } from 'react'
import { ImageIcon, X } from 'lucide-react'
import { useObjectUrl } from '@/hooks/useObjectUrl'
import { REMOVE_PHOTO_LABEL } from '@/constants/assistant'

export interface ChatPhotoAttachmentProps {
  file: File
  onRemove: () => void
  disabled?: boolean
}

export function ChatPhotoAttachment({ file, onRemove, disabled = false }: ChatPhotoAttachmentProps) {
  const url = useObjectUrl(file)
  const [hasPreviewFailed, setHasPreviewFailed] = useState(false)

  useEffect(() => {
    setHasPreviewFailed(false)
  }, [file])

  const showPreview = url !== null && !hasPreviewFailed

  return (
    <div className="flex items-center gap-2 rounded-xl border border-line bg-raised p-1.5">
      {showPreview ? (
        <img
          src={url}
          alt=""
          onError={() => setHasPreviewFailed(true)}
          className="h-10 w-10 shrink-0 rounded-lg object-cover"
        />
      ) : (
        <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-surface text-muted">
          <ImageIcon className="h-5 w-5" />
        </span>
      )}
      <span className="min-w-0 flex-1 truncate text-xs text-foreground">{file.name}</span>
      <button
        type="button"
        onClick={onRemove}
        disabled={disabled}
        className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-subtext transition-colors hover:text-foreground disabled:pointer-events-none disabled:opacity-60"
        aria-label={REMOVE_PHOTO_LABEL}
      >
        <X className="h-4 w-4" />
      </button>
    </div>
  )
}
