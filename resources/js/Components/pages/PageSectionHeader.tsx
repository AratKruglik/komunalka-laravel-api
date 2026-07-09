import type { ReactNode } from 'react'
import { Button, CardDescription, CardHeader, CardTitle } from '../ui'
import type { ButtonProps } from '../ui/Button'

interface PageSectionHeaderCTAButtonProps extends ButtonProps {
  label: ReactNode
  icon?: ReactNode
}

export interface PageSectionHeaderProps {
  title: ReactNode
  description?: ReactNode
  className?: string
  titleClassName?: string
  descriptionClassName?: string
  withBorder?: boolean
  ctaButton?: PageSectionHeaderCTAButtonProps
}

export function PageSectionHeader({
  title,
  description,
  className,
  titleClassName,
  descriptionClassName,
  withBorder = false,
  ctaButton,
}: PageSectionHeaderProps) {
  const headerClasses = [
    'flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between',
    withBorder ? 'border-b border-line pb-5' : '',
    className,
  ]
    .filter(Boolean)
    .join(' ')

  const titleClasses = [
    'text-2xl font-semibold text-foreground',
    titleClassName,
  ]
    .filter(Boolean)
    .join(' ')

  const descriptionClasses = [
    'text-base text-subtext',
    descriptionClassName,
  ]
    .filter(Boolean)
    .join(' ')

  let ctaContent: ReactNode = null

  if (ctaButton) {
    const {
      label,
      icon,
      className: ctaClassName,
      variant = 'primary',
      size = 'md',
      type = 'button',
      ...ctaRest
    } = ctaButton

    ctaContent = (
      <Button
        variant={variant}
        size={size}
        type={type}
        className={[
          'w-full min-w-0 text-sm sm:w-auto sm:min-w-[200px] sm:text-base',
          ctaClassName,
        ]
          .filter(Boolean)
          .join(' ')}
        {...ctaRest}
      >
        {icon}
        <span>{label}</span>
      </Button>
    )
  }

  return (
    <CardHeader className={headerClasses}>
      <div className="space-y-1">
        <CardTitle className={titleClasses}>{title}</CardTitle>
        {description ? (
          <CardDescription className={descriptionClasses}>{description}</CardDescription>
        ) : null}
      </div>
      {ctaContent ? <div className="sm:flex-shrink-0">{ctaContent}</div> : null}
    </CardHeader>
  )
}
