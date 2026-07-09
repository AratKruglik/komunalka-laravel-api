import type { HTMLAttributes } from 'react'
import { tv, type VariantProps } from 'tailwind-variants'

const spinner = tv({
  base: 'inline-block animate-spin rounded-full border-current border-t-transparent',
  variants: {
    size: {
      xs: 'h-3 w-3 border',
      sm: 'h-4 w-4 border-2',
      md: 'h-5 w-5 border-2',
      lg: 'h-6 w-6 border-2',
    },
    tone: {
      primary: 'text-primary',
      warning: 'text-warning',
      danger: 'text-error',
      success: 'text-success',
      info: 'text-info',
    },
  },
  defaultVariants: {
    size: 'md',
    tone: 'primary',
  },
})

export interface SpinnerProps
  extends HTMLAttributes<HTMLSpanElement>,
    VariantProps<typeof spinner> {}

export function Spinner({ className, size, tone, ...props }: SpinnerProps) {
  return <span className={spinner({ size, tone, className })} {...props} />
}
