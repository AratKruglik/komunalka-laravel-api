import { tv } from 'tailwind-variants'

export const card = tv({
  base: ['rounded-xl border bg-white shadow-lg', 'dark:bg-slate-900'],
  variants: {
    border: {
      default: 'border-neutral-200 dark:border-slate-800',
      subtle: 'border-neutral-200/80 dark:border-slate-800',
      none: 'border-transparent',
    },
    padding: {
      none: '',
      sm: 'p-3.5 sm:p-4',
      md: 'p-3.5 sm:p-5 lg:p-6',
      lg: 'p-4 sm:p-5 lg:p-6',
      welcome: 'px-3.5 py-4 sm:px-5 sm:py-5 lg:px-6 lg:py-6',
    },
  },
  defaultVariants: { border: 'default', padding: 'md' },
})
