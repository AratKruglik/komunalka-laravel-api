import type { HTMLAttributes } from 'react'

type BadgeVariant = 'primary' | 'secondary' | 'neutral' | 'success' | 'warning' | 'danger'

export interface BadgeProps extends HTMLAttributes<HTMLSpanElement> {
  variant?: BadgeVariant
}

const variantStyles: Record<BadgeVariant, string> = {
  primary: 'bg-primary/20 text-[var(--color-text-dark)] border border-primary/50',
  secondary: 'bg-[var(--color-secondary-light)]/20 text-[var(--color-secondary-dark)] border border-[var(--color-secondary-light)]/50',
  neutral: 'bg-[var(--color-neutral-100)] text-[var(--color-neutral-700)] border border-[var(--color-neutral-200)]',
  success: 'bg-[var(--color-success)]/15 text-[var(--color-success)] border border-[var(--color-success)]/40',
  warning: 'bg-[var(--color-warning)]/15 text-[var(--color-warning)] border border-[var(--color-warning)]/40',
  danger: 'bg-[var(--color-error)]/15 text-[var(--color-error)] border border-[var(--color-error)]/40',
}

export function Badge({ className = '', variant = 'primary', children, ...props }: BadgeProps) {
  return (
    <span
      className={[
        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide',
        variantStyles[variant],
        className,
      ]
        .filter(Boolean)
        .join(' ')}
      {...props}
    >
      {children}
    </span>
  )
}
