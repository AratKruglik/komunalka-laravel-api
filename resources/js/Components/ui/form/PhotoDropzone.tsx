import { useRef, useState } from 'react'
import type {
  ChangeEvent,
  DragEvent,
  InputHTMLAttributes,
  MutableRefObject,
  ReactNode,
} from 'react'
import { Button } from '../Button'

interface InputPropsWithRef extends InputHTMLAttributes<HTMLInputElement> {
  ref?: ((element: HTMLInputElement | null) => void) | MutableRefObject<HTMLInputElement | null>
}

export interface PhotoDropzoneProps {
  id: string
  className?: string
  fileName?: string | null
  previewUrl?: string | null
  emptyIcon?: ReactNode
  emptyTitle?: ReactNode
  emptyDescription?: ReactNode
  helperText?: ReactNode
  buttonLabel?: string
  clearLabel?: string
  onFilesSelected?: (files: FileList | null) => void
  onClear?: () => void
  inputProps?: InputPropsWithRef
  previewHeight?: number
  variant?: 'default' | 'full'
}

export function PhotoDropzone({
  id,
  className = '',
  fileName,
  previewUrl,
  emptyIcon,
  emptyTitle,
  emptyDescription,
  helperText,
  buttonLabel = 'Завантажити фото',
  clearLabel = 'Видалити фото',
  onFilesSelected,
  onClear,
  inputProps,
  previewHeight = 260,
  variant = 'default',
}: PhotoDropzoneProps) {
  const inputRef = useRef<HTMLInputElement | null>(null)
  const [isDragActive, setIsDragActive] = useState(false)
  const hasPreview = Boolean(previewUrl)

  const { ref: externalRef, onChange: externalOnChange, ...restInputProps } = inputProps ?? {}

  const assignRef = (element: HTMLInputElement | null) => {
    inputRef.current = element
    if (!externalRef) {
      return
    }

    if (typeof externalRef === 'function') {
      externalRef(element)
      return
    }

    externalRef.current = element
  }

  const emitFiles = (files: FileList | null) => {
    onFilesSelected?.(files)
  }

  const handleInputChange = (event: ChangeEvent<HTMLInputElement>) => {
    externalOnChange?.(event)
    emitFiles(event.target.files ?? null)
    setIsDragActive(false)
  }

  const handleDragOver = (event: DragEvent<HTMLLabelElement>) => {
    event.preventDefault()
    event.stopPropagation()
    setIsDragActive(true)
  }

  const handleDragLeave = (event: DragEvent<HTMLLabelElement>) => {
    event.preventDefault()
    event.stopPropagation()
    if (!event.currentTarget.contains(event.relatedTarget as Node | null)) {
      setIsDragActive(false)
    }
  }

  const handleDrop = (event: DragEvent<HTMLLabelElement>) => {
    event.preventDefault()
    event.stopPropagation()
    setIsDragActive(false)
    const files = event.dataTransfer?.files?.length ? event.dataTransfer.files : null
    if (!files) {
      return
    }

    emitFiles(files)
  }

  const handleClear = () => {
    if (inputRef.current) {
      inputRef.current.value = ''
    }
    onClear?.()
  }

  const wrapperClasses = [
    'flex min-h-[220px] cursor-pointer flex-col gap-3 rounded-2xl border border-dashed text-subtext transition',
    variant === 'full' ? 'px-5 py-5 sm:px-6 sm:py-6' : 'px-4 py-4',
    isDragActive
      ? 'border-primary bg-primary/10'
      : 'border-line bg-surface hover:border-primary hover:bg-primary/5',
    hasPreview && variant === 'default' ? 'items-stretch text-left' : 'items-center text-center',
    className,
  ]
    .filter(Boolean)
    .join(' ')

  return (
    <label
      htmlFor={id}
      className={wrapperClasses}
      onDragEnter={handleDragOver}
      onDragOver={handleDragOver}
      onDragLeave={handleDragLeave}
      onDrop={handleDrop}
    >
      {hasPreview ? (
        variant === 'full' ? (
          <div className="flex w-full flex-col gap-4 text-left">
            <div
              className="relative w-full overflow-hidden rounded-[24px] bg-raised shadow-inner"
              style={{ minHeight: previewHeight }}
            >
              <img src={previewUrl ?? ''} alt={fileName ?? 'Превʼю фото'} className="h-full w-full object-cover" />
            </div>
            <div className="text-sm text-subtext">
              <p className="font-semibold text-foreground">{fileName}</p>
              {helperText ? <p className="text-xs text-muted">{helperText}</p> : null}
            </div>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              className="self-start"
              onClick={(event) => {
                event.preventDefault()
                handleClear()
              }}
            >
              {clearLabel}
            </Button>
          </div>
        ) : (
          <div className="flex w-full flex-col gap-3">
            <div className="w-full rounded-lg border border-line bg-raised shadow-inner">
              <div className="w-full overflow-hidden rounded-lg bg-surface" style={{ minHeight: previewHeight }}>
                <img
                  src={previewUrl ?? ''}
                  alt={fileName ?? 'Превʼю фото'}
                  className="h-full w-full object-contain"
                />
              </div>
            </div>
            <div className="text-center text-sm text-muted sm:text-left">
              <p className="text-sm font-medium text-foreground">{fileName}</p>
              {helperText ? <p className="text-xs text-muted">{helperText}</p> : null}
            </div>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              className="self-center sm:self-start"
              onClick={(event) => {
                event.preventDefault()
                handleClear()
              }}
            >
              {clearLabel}
            </Button>
          </div>
        )
      ) : (
        <>
          {emptyIcon}
          {emptyTitle ? <p className="text-base font-medium text-foreground">{emptyTitle}</p> : null}
          {emptyDescription ? <p className="text-sm text-muted">{emptyDescription}</p> : null}
          {buttonLabel ? (
            <Button type="button" variant="secondary" size="sm" className="pointer-events-none">
              {buttonLabel}
            </Button>
          ) : null}
        </>
      )}
      <input
        id={id}
        type="file"
        className="sr-only"
        onChange={handleInputChange}
        ref={(element) => assignRef(element)}
        {...restInputProps}
      />
    </label>
  )
}
