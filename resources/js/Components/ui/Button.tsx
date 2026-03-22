import { forwardRef } from 'react'
import type { ButtonHTMLAttributes } from 'react'
import { tv, type VariantProps } from 'tailwind-variants'
import { Spinner } from './Spinner'

const button = tv({
  base: 'inline-flex items-center justify-center gap-2 rounded-md font-medium transition-colors duration-150 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:pointer-events-none disabled:opacity-60 disabled:bg-gray-300 dark:disabled:bg-slate-700 dark:disabled:text-slate-400',
  variants: {
    variant: {
      solid: '',
      outline: 'border bg-white dark:bg-slate-900',
      ghost: 'bg-transparent',
      link: 'p-0 h-auto underline-offset-4 hover:underline focus-visible:outline-none',
    },
    tone: {
      primary: '',
      neutral: '',
      secondary: '',
      success: '',
      warning: '',
      danger: '',
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
  compoundVariants: [
    {
      variant: 'solid',
      tone: 'primary',
      className: 'bg-primary text-gray-900 hover:bg-primary-dark active:bg-yellow-600',
    },
    {
      variant: 'solid',
      tone: 'neutral',
      className: 'bg-neutral-800 text-white hover:bg-neutral-900 active:bg-neutral-950',
    },
    {
      variant: 'solid',
      tone: 'secondary',
      className:
        'bg-[var(--color-secondary)] text-white hover:bg-[var(--color-secondary-dark)] active:bg-[var(--color-secondary-dark)]/90',
    },
    {
      variant: 'solid',
      tone: 'success',
      className: 'bg-[var(--color-success)] text-white hover:bg-green-700 active:bg-green-800',
    },
    {
      variant: 'solid',
      tone: 'warning',
      className: 'bg-[var(--color-warning)] text-neutral-900 hover:bg-orange-700 active:bg-orange-800',
    },
    {
      variant: 'solid',
      tone: 'danger',
      className: 'bg-[var(--color-error)] text-white hover:bg-red-700 active:bg-red-800',
    },
    {
      variant: 'outline',
      tone: 'primary',
      className:
        'border-gray-300 text-text-dark hover:bg-gray-50 dark:border-slate-700 dark:text-slate-100 dark:hover:bg-slate-800',
    },
    {
      variant: 'outline',
      tone: 'neutral',
      className:
        'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-slate-700 dark:text-slate-100 dark:hover:bg-slate-800',
    },
    {
      variant: 'outline',
      tone: 'secondary',
      className:
        'border-[var(--color-secondary)] text-[var(--color-secondary)] hover:bg-[var(--color-secondary)]/10 dark:border-[var(--color-secondary)] dark:text-[var(--color-secondary)] dark:hover:bg-[var(--color-secondary)]/15',
    },
    {
      variant: 'outline',
      tone: 'success',
      className:
        'border-[var(--color-success)] text-[var(--color-success)] hover:bg-[var(--color-success)]/10 dark:hover:bg-[var(--color-success)]/15',
    },
    {
      variant: 'outline',
      tone: 'warning',
      className:
        'border-[var(--color-warning)] text-[var(--color-warning)] hover:bg-[var(--color-warning)]/10 dark:hover:bg-[var(--color-warning)]/15',
    },
    {
      variant: 'outline',
      tone: 'danger',
      className:
        'border-[var(--color-error)] text-[var(--color-error)] hover:bg-[var(--color-error)]/10 dark:hover:bg-[var(--color-error)]/15',
    },
    {
      variant: 'ghost',
      tone: 'primary',
      className: 'text-gray-900 hover:bg-primary/10 dark:text-amber-200 dark:hover:bg-amber-200/15',
    },
    {
      variant: 'ghost',
      tone: 'neutral',
      className: 'text-gray-600 hover:bg-gray-100 dark:text-slate-100 dark:hover:bg-slate-800',
    },
    {
      variant: 'ghost',
      tone: 'secondary',
      className: 'text-[var(--color-secondary)] hover:bg-[var(--color-secondary)]/10',
    },
    {
      variant: 'ghost',
      tone: 'success',
      className: 'text-[var(--color-success)] hover:bg-[var(--color-success)]/10',
    },
    {
      variant: 'ghost',
      tone: 'warning',
      className: 'text-[var(--color-warning)] hover:bg-[var(--color-warning)]/10',
    },
    {
      variant: 'ghost',
      tone: 'danger',
      className: 'text-[var(--color-error)] hover:bg-[var(--color-error)]/10',
    },
    {
      variant: 'link',
      tone: 'primary',
      className: 'text-yellow-600',
    },
    {
      variant: 'link',
      tone: 'neutral',
      className: 'text-gray-600',
    },
    {
      variant: 'link',
      tone: 'secondary',
      className: 'text-[var(--color-secondary)]',
    },
    {
      variant: 'link',
      tone: 'success',
      className: 'text-[var(--color-success)]',
    },
    {
      variant: 'link',
      tone: 'warning',
      className: 'text-[var(--color-warning)]',
    },
    {
      variant: 'link',
      tone: 'danger',
      className: 'text-[var(--color-error)]',
    },
    {
      variant: 'link',
      size: ['sm', 'md', 'lg', 'xl', 'icon'],
      className: 'h-auto px-0 py-0 text-sm',
    },
  ],
  defaultVariants: {
    variant: 'solid',
    tone: 'primary',
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
    tone,
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
      className={button({ variant, tone, size, fullWidth, className })}
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
