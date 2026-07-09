import { forwardRef } from 'react'
import type { HTMLAttributes } from 'react'

type FormMessageVariant = 'default' | 'success' | 'error'

export interface FormMessageProps extends HTMLAttributes<HTMLParagraphElement> {
  variant?: FormMessageVariant
}

const variantStyles: Record<FormMessageVariant, string> = {
  default: 'text-text-secondary',
  success: 'text-success',
  error: 'text-error',
}

export const FormMessage = forwardRef<HTMLParagraphElement, FormMessageProps>(
  function FormMessage({ className = '', variant = 'default', ...props }, ref) {
    return (
      <p
        ref={ref}
        className={[
          'text-sm leading-snug',
          variantStyles[variant],
          className,
        ]
          .filter(Boolean)
          .join(' ')}
        {...props}
      />
    )
  },
)
