import { forwardRef } from 'react'
import type { TextareaHTMLAttributes } from 'react'

export interface TextareaProps extends TextareaHTMLAttributes<HTMLTextAreaElement> {
  isInvalid?: boolean
}

export const Textarea = forwardRef<HTMLTextAreaElement, TextareaProps>(function Textarea(
  { className = '', isInvalid = false, rows = 4, ...props },
  ref,
) {
  const baseClasses =
    'w-full rounded-md border-[0.5px] border-border bg-bg-raised px-4 py-2.5 text-base text-text-primary placeholder:text-text-muted transition-colors focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary'

  const invalidClasses =
    'border-error focus:ring-error focus:border-error'

  return (
    <textarea
      ref={ref}
      rows={rows}
      className={[
        baseClasses,
        isInvalid ? invalidClasses : '',
        'disabled:cursor-not-allowed disabled:bg-bg-surface disabled:text-text-muted disabled:focus:ring-0 disabled:focus:border-border read-only:focus:ring-0 read-only:focus:border-border',
        'resize-none',
        className,
      ]
        .filter(Boolean)
        .join(' ')}
      aria-invalid={isInvalid || undefined}
      {...props}
    />
  )
})
