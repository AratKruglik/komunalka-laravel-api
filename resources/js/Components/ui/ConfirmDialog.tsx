import { useEffect, useRef, type ReactNode } from 'react'
import { AlertTriangle, X } from 'lucide-react'
import { tv } from 'tailwind-variants'
import { Button } from './Button'

const dialog = tv({
  slots: {
    overlay: [
      'fixed inset-0 z-50 bg-black/50 backdrop-blur-sm',
      'animate-in fade-in-0 duration-200',
    ],
    container: [
      'relative',
      'fixed left-1/2 top-1/2 z-50 -translate-x-1/2 -translate-y-1/2',
      'w-[420px] max-w-[calc(100vw-2rem)]',
      'rounded-xl border border-gray-200 bg-white p-6 shadow-xl',
      'dark:border-slate-800 dark:bg-slate-900',
      'animate-in fade-in-0 zoom-in-95 duration-200',
    ],
    closeButton: [
      'absolute right-4 top-4 rounded-full p-1.5 text-gray-400 transition-colors',
      'hover:bg-gray-100 hover:text-gray-600',
      'dark:hover:bg-slate-800 dark:hover:text-slate-200',
      'focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary',
    ],
    body: 'flex flex-col items-center text-center',
    iconWrapper: [
      'mb-4 flex h-12 w-12 items-center justify-center rounded-full',
    ],
    title: 'text-lg font-semibold text-gray-900 dark:text-slate-50',
    description: 'mt-2 max-w-[320px] text-sm text-gray-600 dark:text-slate-400',
    footer: 'mt-6 flex justify-center gap-3',
  },
  variants: {
    variant: {
      danger: {
        iconWrapper: 'bg-red-100 text-red-600 dark:bg-red-950/50 dark:text-red-400',
      },
      warning: {
        iconWrapper: 'bg-amber-100 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400',
      },
      info: {
        iconWrapper: 'bg-blue-100 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400',
      },
    },
  },
  defaultVariants: {
    variant: 'danger',
  },
})

interface ConfirmDialogProps {
  isOpen: boolean
  onClose: () => void
  onConfirm: () => void
  title: string
  description: ReactNode
  confirmLabel?: string
  cancelLabel?: string
  variant?: 'danger' | 'warning' | 'info'
  isLoading?: boolean
  icon?: ReactNode
}

export function ConfirmDialog({
  isOpen,
  onClose,
  onConfirm,
  title,
  description,
  confirmLabel = 'Підтвердити',
  cancelLabel = 'Скасувати',
  variant = 'danger',
  isLoading = false,
  icon,
}: ConfirmDialogProps) {
  const dialogRef = useRef<HTMLDivElement>(null)
  const styles = dialog({ variant })

  useEffect(() => {
    if (!isOpen) return

    function handleEscape(event: KeyboardEvent) {
      if (event.key === 'Escape' && !isLoading) {
        onClose()
      }
    }

    document.addEventListener('keydown', handleEscape)
    document.body.style.overflow = 'hidden'

    return () => {
      document.removeEventListener('keydown', handleEscape)
      document.body.style.overflow = ''
    }
  }, [isOpen, isLoading, onClose])

  if (!isOpen) return null

  const confirmTone = variant === 'danger' ? 'danger' : variant === 'warning' ? 'warning' : 'primary'

  return (
    <>
      <div className={styles.overlay()} onClick={isLoading ? undefined : onClose} aria-hidden />
      <div
        ref={dialogRef}
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="dialog-title"
        aria-describedby="dialog-description"
        className={styles.container()}
      >
        <button
          type="button"
          onClick={onClose}
          disabled={isLoading}
          className={styles.closeButton()}
          aria-label="Закрити"
        >
          <X className="h-4 w-4" />
        </button>

        <div className={styles.body()}>
          <div className={styles.iconWrapper()}>
            {icon ?? <AlertTriangle className="h-6 w-6" />}
          </div>
          <h2 id="dialog-title" className={styles.title()}>
            {title}
          </h2>
          <p id="dialog-description" className={styles.description()}>
            {description}
          </p>
        </div>

        <div className={styles.footer()}>
          <Button
            type="button"
            variant="outline"
            tone="neutral"
            onClick={onClose}
            disabled={isLoading}
          >
            {cancelLabel}
          </Button>
          <Button
            type="button"
            variant="solid"
            tone={confirmTone}
            onClick={onConfirm}
            loading={isLoading}
          >
            {confirmLabel}
          </Button>
        </div>
      </div>
    </>
  )
}
