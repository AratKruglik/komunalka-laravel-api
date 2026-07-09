import { forwardRef } from 'react'
import type { ButtonHTMLAttributes } from 'react'
import { tv, type VariantProps } from 'tailwind-variants'
import { Spinner } from './Spinner'

const button = tv({
  base: 'inline-flex items-center justify-center gap-2 rounded-md font-medium transition-colors duration-200 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:pointer-events-none disabled:opacity-50',
  variants: {
    variant: {
      primary: 'bg-primary text-text-inverse hover:bg-primary-dark active:bg-primary-dark',
      secondary:
        'border border-border bg-transparent text-text-primary hover:border-text-muted',
      ghost: 'bg-transparent text-text-secondary hover:bg-bg-surface hover:text-text-primary',
      danger: 'bg-error text-white hover:bg-error/90 active:bg-error/80',
    },
    size: {
      sm: 'h-9 px-3 text-sm',
      md: 'h-10 px-4 text-sm',
      lg: 'h-11 px-5 text-base',
      xl: 'h-12 px-6 text-base',
      icon: 'h-10 w-10 p-0 rounded-full gap-0',
    },
    fullWidth: {
      true: 'w-full',
    },
  },
  defaultVariants: {
    variant: 'primary',
    size: 'md',
  },
})

export interface ButtonProps
  extends ButtonHTMLAttributes<HTMLButtonElement>,
    VariantProps<typeof button> {
  loading?: boolean
  loadingText?: string
}

export const Button = forwardRef<HTMLButtonElement, ButtonProps>(function Button(
  {
    className,
    variant,
    size,
    fullWidth,
    loading = false,
    loadingText,
    disabled,
    children,
    type = 'button',
    ...props
  },
  ref,
) {
  const isDisabled = disabled || loading
  const content = loading ? loadingText ?? children : children

  return (
    <button
      ref={ref}
      type={type}
      disabled={isDisabled}
      className={button({ variant, size, fullWidth, className })}
      {...props}
    >
      {loading ? (
        <>
          <Spinner size="sm" className="text-current" />
          {content}
        </>
      ) : (
        content
      )}
    </button>
  )
})
