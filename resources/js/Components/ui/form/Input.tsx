import { forwardRef } from 'react'
import type { InputHTMLAttributes, ReactNode } from 'react'
import { tv } from 'tailwind-variants'

const inputVariants = tv({
  base: 'w-full py-2.5 rounded-md border border-border text-base text-text-primary placeholder:text-text-muted bg-bg-raised transition-colors focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary disabled:cursor-not-allowed disabled:bg-bg-surface disabled:text-text-muted disabled:focus:ring-0 disabled:focus:border-border read-only:focus:ring-0 read-only:focus:border-border',
  variants: {
    isInvalid: {
      true: 'border-error focus:ring-error focus:border-error',
    },
    hasLeadingIcon: {
      true: 'pl-11',
      false: 'pl-4',
    },
    hasEndAdornment: {
      true: 'pr-12',
      false: 'pr-4',
    },
  },
  defaultVariants: {
    isInvalid: false,
    hasLeadingIcon: false,
    hasEndAdornment: false,
  },
})

export interface InputProps extends InputHTMLAttributes<HTMLInputElement> {
  isInvalid?: boolean
  leadingIcon?: ReactNode
  endAdornment?: ReactNode
  wrapperClassName?: string
}

export const Input = forwardRef<HTMLInputElement, InputProps>(function Input(
  {
    className,
    wrapperClassName = '',
    isInvalid = false,
    leadingIcon,
    endAdornment,
    ...props
  },
  ref,
) {
  const hasLeadingIcon = Boolean(leadingIcon)
  const hasEndAdornment = Boolean(endAdornment)

  return (
    <div className={['relative', wrapperClassName].filter(Boolean).join(' ')}>
      {leadingIcon ? (
        <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-text-muted">
          {leadingIcon}
        </span>
      ) : null}
      <input
        ref={ref}
        className={inputVariants({
          isInvalid,
          hasLeadingIcon,
          hasEndAdornment,
          className,
        })}
        aria-invalid={isInvalid || undefined}
        {...props}
      />
      {endAdornment ? (
        <span className="absolute inset-y-0 right-0 flex items-center pr-3">
          {endAdornment}
        </span>
      ) : null}
    </div>
  )
})
