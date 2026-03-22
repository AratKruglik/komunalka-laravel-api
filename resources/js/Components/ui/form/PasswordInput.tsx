import { useMemo, useState } from 'react'
import type { InputHTMLAttributes, ReactNode } from 'react'
import { Eye, EyeOff, Lock, CheckCircle2, Circle } from 'lucide-react'
import { Input, type InputProps } from './Input'
import { Label } from './Label'
import { FormMessage } from './FormMessage'

export type PasswordStrength = 'none' | 'weak' | 'medium' | 'strong'

export interface PasswordRequirement {
  label: string
  test: (password: string) => boolean
}

export const defaultPasswordRequirements: PasswordRequirement[] = [
  { label: 'Мінімум 8 символів', test: (p) => p.length >= 8 },
  { label: 'Мінімум 1 велика літера', test: (p) => /[A-Z]/.test(p) },
  { label: 'Мінімум 1 цифра', test: (p) => /\d/.test(p) },
]

const passwordStrengthStyles: Record<
  PasswordStrength,
  { width: string; barClass: string; textClass: string; label: string }
> = {
  none: { width: '0%', barClass: 'bg-neutral-200 dark:bg-slate-700', textClass: 'text-neutral-500 dark:text-slate-400', label: 'Не введено' },
  weak: { width: '33%', barClass: 'bg-red-400 dark:bg-red-500', textClass: 'text-red-600 dark:text-red-300', label: 'Слабкий' },
  medium: { width: '66%', barClass: 'bg-amber-400 dark:bg-amber-400', textClass: 'text-amber-700 dark:text-amber-200', label: 'Середній' },
  strong: { width: '100%', barClass: 'bg-emerald-500 dark:bg-emerald-400', textClass: 'text-emerald-600 dark:text-emerald-200', label: 'Надійний' },
}

export const getPasswordStrength = (
  password: string,
  requirements: PasswordRequirement[] = defaultPasswordRequirements,
): PasswordStrength => {
  if (!password) return 'none'
  const satisfied = requirements.filter((req) => req.test(password)).length

  if (satisfied <= 1) return 'weak'
  if (satisfied === 2) return 'medium'
  return 'strong'
}

export interface PasswordInputProps
  extends Omit<InputHTMLAttributes<HTMLInputElement>, 'value' | 'onChange'> {
  id: string
  label?: ReactNode
  value: string
  onChange: (value: string) => void
  error?: string
  showStrength?: boolean
  requirements?: PasswordRequirement[]
  inputProps?: Omit<InputProps, 'value' | 'onChange'>
  endAdornment?: ReactNode
}

export function PasswordInput({
  id,
  label,
  value,
  onChange,
  error,
  disabled,
  showStrength = true,
  requirements = defaultPasswordRequirements,
  inputProps,
  endAdornment,
  ...rest
}: PasswordInputProps) {
  const [isVisible, setIsVisible] = useState(false)
  const strength = useMemo(() => getPasswordStrength(value, requirements), [value, requirements])
  const strengthStyle = passwordStrengthStyles[strength]

  return (
    <div className="space-y-1.5" {...rest}>
      {label ? <Label htmlFor={id}>{label}</Label> : null}

      <Input
        id={id}
        type={isVisible ? 'text' : 'password'}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        leadingIcon={<Lock className="h-4 w-4 text-neutral-500" />}
        endAdornment={
          endAdornment ?? (
            <button
              type="button"
              onClick={() => setIsVisible(!isVisible)}
              className="rounded-md p-2 text-neutral-500 transition hover:text-neutral-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary disabled:cursor-not-allowed dark:text-slate-400 dark:hover:text-slate-200"
              aria-label={isVisible ? 'Приховати пароль' : 'Показати пароль'}
              disabled={disabled}
            >
              {isVisible ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
            </button>
          )
        }
        disabled={disabled}
        isInvalid={Boolean(error)}
        {...inputProps}
      />

      {error ? <FormMessage variant="error">{error}</FormMessage> : null}

      {showStrength ? (
        <div className="space-y-2">
          <div className="h-1 w-full overflow-hidden rounded-full bg-neutral-200 dark:bg-slate-700">
            <div
              className={['h-full transition-all duration-300', strengthStyle.barClass].join(' ')}
              style={{ width: strengthStyle.width }}
            />
          </div>
          <div className="flex items-center justify-between text-xs">
            <span className="text-neutral-500 dark:text-slate-400">Надійність паролю:</span>
            <span className={['font-medium', strengthStyle.textClass].join(' ')}>
              {strengthStyle.label}
            </span>
          </div>

          <ul className="space-y-1">
            {requirements.map((requirement, index) => {
              const isSatisfied = requirement.test(value)
              return (
                <li
                  key={index}
                  className="flex items-center gap-2 text-xs text-neutral-600 dark:text-slate-400"
                >
                  {isSatisfied ? (
                    <CheckCircle2 className="h-3 w-3 text-emerald-500" />
                  ) : (
                    <Circle className="h-3 w-3" />
                  )}
                  {requirement.label}
                </li>
              )
            })}
          </ul>
        </div>
      ) : null}
    </div>
  )
}
