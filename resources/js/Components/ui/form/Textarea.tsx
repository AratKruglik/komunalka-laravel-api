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
    'w-full rounded-md border-[0.5px] border-gray-300 bg-white px-4 py-2.5 text-base text-dark placeholder:text-text-placeholder transition-colors focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:placeholder:text-slate-400 dark:focus:border-amber-300 dark:focus:ring-amber-300'

  const invalidClasses =
    'border-error focus:ring-error focus:border-error'

  return (
    <textarea
      ref={ref}
      rows={rows}
      className={[
        baseClasses,
        isInvalid ? invalidClasses : '',
        'disabled:cursor-not-allowed disabled:bg-gray-100 disabled:text-gray-400 disabled:focus:ring-0 disabled:focus:border-gray-300 read-only:focus:ring-0 read-only:focus:border-gray-300 dark:disabled:bg-slate-800 dark:disabled:text-slate-500 dark:disabled:focus:border-slate-700 dark:read-only:focus:border-slate-700',
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
