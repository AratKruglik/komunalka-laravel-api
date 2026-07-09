import { forwardRef, type ComponentPropsWithRef, type ReactNode } from 'react'
import { tv, type VariantProps } from 'tailwind-variants'
import { ChevronDown } from 'lucide-react'

const selectStyles = tv({
  slots: {
    wrapper: 'relative',
    select:
      'w-full appearance-none rounded-md border border-line bg-raised bg-no-repeat py-2.5 pr-10 text-base text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary disabled:cursor-not-allowed disabled:bg-surface disabled:text-muted disabled:focus:ring-0 disabled:focus:border-line',
    iconWrapper: 'pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-muted',
  },
  variants: {
    isInvalid: {
      true: {
        select: 'border-error focus:border-error focus:ring-error',
      },
    },
    hasLeadingIcon: {
      true: {
        select: 'pl-11',
      },
      false: {
        select: 'pl-4',
      },
    },
  },
  defaultVariants: {
    isInvalid: false,
    hasLeadingIcon: false,
  },
})

export interface SelectProps
  extends ComponentPropsWithRef<'select'>,
    VariantProps<typeof selectStyles> {
  leadingIcon?: ReactNode
}

export const Select = forwardRef<HTMLSelectElement, SelectProps>(function Select(
  { className, isInvalid, hasLeadingIcon, leadingIcon, children, ...props },
  ref,
) {
  const { wrapper, select, iconWrapper } = selectStyles({ isInvalid, hasLeadingIcon })

  return (
    <div className={wrapper()}>
      {leadingIcon ? (
        <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-muted">
          {leadingIcon}
        </span>
      ) : null}
      <select ref={ref} className={select({ className })} {...props}>
        {children}
      </select>
      <span className={iconWrapper()}>
        <ChevronDown className="h-5 w-5 text-muted" />
      </span>
    </div>
  )
})
