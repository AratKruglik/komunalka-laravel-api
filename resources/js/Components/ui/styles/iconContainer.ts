import { tv } from 'tailwind-variants'

export const iconContainer = tv({
  base: 'grid place-items-center shrink-0',
  variants: {
    size: {
      sm: 'h-8 w-8',
      md: 'h-9 w-9 sm:h-10 sm:w-10',
      lg: 'h-11 w-11',
      xl: 'h-12 w-12',
    },
    shape: {
      circle: 'rounded-full',
      square: 'rounded-lg',
    },
  },
  defaultVariants: { size: 'md', shape: 'circle' },
})
