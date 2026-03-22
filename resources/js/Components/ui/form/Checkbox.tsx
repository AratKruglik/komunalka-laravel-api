import { forwardRef } from 'react'
import type { InputHTMLAttributes } from 'react'

export type CheckboxProps = Omit<InputHTMLAttributes<HTMLInputElement>, 'type'>

export const Checkbox = forwardRef<HTMLInputElement, CheckboxProps>(function Checkbox(
  { className = '', ...props },
  ref,
) {
  return (
    <input
      ref={ref}
      type="checkbox"
      className={[
        'h-4 w-4 rounded border-[0.5px] border-gray-400 bg-white text-primary transition-colors dark:border-slate-600 dark:bg-slate-800',
        'focus:outline-none focus:ring-1 focus:ring-primary',
        'disabled:cursor-not-allowed disabled:bg-gray-100 disabled:opacity-70 disabled:focus:ring-0',
        'accent-primary',
        className,
      ]
        .filter(Boolean)
        .join(' ')}
      {...props}
    />
  )
})
