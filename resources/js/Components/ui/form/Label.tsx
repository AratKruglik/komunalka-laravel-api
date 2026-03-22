import { forwardRef, type ComponentPropsWithRef } from 'react'
import { tv, type VariantProps } from 'tailwind-variants'

const labelStyles = tv({
  base: 'block text-sm font-medium text-neutral-800 dark:text-slate-100',
  variants: {
    isRequired: {
      true: "after:ml-0.5 after:text-red-500 after:content-['*']",
    },
  },
})

export interface LabelProps
  extends ComponentPropsWithRef<'label'>,
    VariantProps<typeof labelStyles> {}

export const Label = forwardRef<HTMLLabelElement, LabelProps>(function Label(
  { className, isRequired, ...props },
  ref,
) {
  return <label ref={ref} className={labelStyles({ isRequired, className })} {...props} />
})
