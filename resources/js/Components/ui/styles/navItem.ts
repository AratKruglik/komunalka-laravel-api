import { tv } from 'tailwind-variants'

export const navItem = tv({
  base: ['flex items-center gap-3 px-3 py-2 rounded-lg', 'transition-colors'],
  variants: {
    state: {
      default: 'hover:bg-gray-100 dark:hover:bg-slate-800',
      active: 'bg-primary-bg dark:bg-amber-300/20',
    },
  },
  defaultVariants: { state: 'default' },
})
