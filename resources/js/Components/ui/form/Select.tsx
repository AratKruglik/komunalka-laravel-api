import { forwardRef, type ComponentPropsWithRef, type ReactNode } from 'react'
import { tv, type VariantProps } from 'tailwind-variants'
import { ChevronDown } from 'lucide-react'

const selectStyles = tv({
  slots: {
    wrapper: 'relative',
    select:
      'w-full appearance-none rounded-md border border-neutral-200 bg-white bg-no-repeat py-2.5 pr-10 text-base text-dark transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary disabled:cursor-not-allowed disabled:bg-neutral-100 disabled:text-neutral-400 disabled:focus:ring-0 disabled:focus:border-neutral-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 dark:focus:border-amber-300 dark:focus:ring-amber-300 dark:disabled:bg-slate-800 dark:disabled:text-slate-500 dark:disabled:focus:border-slate-700',
    iconWrapper: 'pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-400 dark:text-slate-400',
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
        <span className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
          {leadingIcon}
        </span>
      ) : null}
      <select ref={ref} className={select({ className })} {...props}>
        {children}
      </select>
      <span className={iconWrapper()}>
        <ChevronDown className="h-5 w-5 text-gray-400" />
      </span>
    </div>
  )
})
