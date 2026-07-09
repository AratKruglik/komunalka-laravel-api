import { forwardRef, isValidElement } from 'react'
import type { ButtonHTMLAttributes, ReactNode } from 'react'
import type { LucideIcon } from 'lucide-react'

export interface RadioCardProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  title: string
  description?: string
  icon?: LucideIcon | ReactNode
  selected?: boolean
  helperText?: ReactNode
  iconClassName?: string
}

function renderIcon(icon?: LucideIcon | ReactNode, iconClassName?: string) {
  if (!icon) {
    return null
  }

  if (isValidElement(icon) || typeof icon === 'string' || typeof icon === 'number') {
    return icon
  }

  const IconComponent = icon as LucideIcon
  return <IconComponent className={iconClassName ?? 'h-6 w-6'} />
}

export const RadioCard = forwardRef<HTMLButtonElement, RadioCardProps>(function RadioCard(
  {
    title,
    description,
    icon,
    helperText,
    selected = false,
    disabled = false,
    className = '',
    iconClassName,
    type = 'button',
    ...props
  },
  ref,
) {
  return (
    <button
      ref={ref}
      type={type}
      className={[
        'w-full rounded-lg border p-4 text-left transition-all',
        selected
          ? 'border-primary bg-primary/10 shadow-[var(--shadow-sm)]'
          : 'border-border hover:border-primary/60 hover:bg-bg-surface',
        disabled ? 'cursor-not-allowed opacity-60 hover:bg-bg-raised' : '',
        className,
      ]
        .filter(Boolean)
        .join(' ')}
      aria-pressed={selected}
      disabled={disabled}
      {...props}
    >
      <div className="flex items-center gap-3">
        {icon ? (
          <span
            className={[
              'flex h-12 w-12 shrink-0 items-center justify-center rounded-full border-2 aspect-square',
              selected
                ? 'border-primary bg-bg-raised text-primary'
                : 'border-border bg-bg-surface text-text-muted',
            ]
              .filter(Boolean)
              .join(' ')}
          >
            {renderIcon(icon, iconClassName)}
          </span>
        ) : null}
        <div>
          <p
            className={[
              'text-base font-semibold',
              selected ? 'text-text-primary' : 'text-text-secondary',
            ]
              .filter(Boolean)
              .join(' ')}
          >
            {title}
          </p>
          {description ? (
            <p className="text-sm text-text-muted">{description}</p>
          ) : null}
        </div>
      </div>
      {helperText ? (
        <div className="mt-3 text-sm text-text-muted">{helperText}</div>
      ) : null}
    </button>
  )
})
