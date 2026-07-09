import { useEffect, useRef, useState } from 'react'
import { Check, Laptop2, Moon, Sun } from 'lucide-react'
import { useTheme } from '../theme/ThemeProvider'

type ThemeOption = 'light' | 'dark' | 'system'

const themeOptions: Array<{ value: ThemeOption; label: string; icon: typeof Sun }> = [
  { value: 'light', label: 'Світла', icon: Sun },
  { value: 'dark', label: 'Темна', icon: Moon },
  { value: 'system', label: 'Системна', icon: Laptop2 },
]

interface ThemeToggleProps {
  className?: string
}

export function ThemeToggle({ className }: ThemeToggleProps) {
  const { theme, resolvedTheme, setTheme } = useTheme()
  const [open, setOpen] = useState(false)
  const menuRef = useRef<HTMLDivElement | null>(null)

  useEffect(() => {
    if (!open) {
      return
    }

    const handleClickOutside = (event: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(event.target as Node | null)) {
        setOpen(false)
      }
    }

    const handleEscape = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        setOpen(false)
      }
    }

    document.addEventListener('mousedown', handleClickOutside)
    document.addEventListener('keydown', handleEscape)

    return () => {
      document.removeEventListener('mousedown', handleClickOutside)
      document.removeEventListener('keydown', handleEscape)
    }
  }, [open])

  const CurrentIcon =
    themeOptions.find((option) => option.value === (theme === 'system' ? resolvedTheme : theme))
      ?.icon ?? Sun

  return (
    <div className={['relative', className].filter(Boolean).join(' ')} ref={menuRef}>
      <button
        type="button"
        aria-haspopup="menu"
        aria-expanded={open}
        onClick={() => setOpen((prev) => !prev)}
        className="inline-flex h-9 w-9 items-center justify-center rounded-full border border-border text-text-secondary transition-colors hover:bg-bg-surface hover:text-text-primary focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary sm:h-10 sm:w-10"
        title="Перемкнути тему"
      >
        <CurrentIcon className="h-4 w-4" strokeWidth={1.7} />
      </button>

      {open ? (
        <div
          role="menu"
          aria-label="Вибір теми"
          className="absolute right-0 top-full z-10 mt-2 w-48 overflow-hidden rounded-xl border border-border bg-bg-raised shadow-lg"
        >
          <ul className="divide-y divide-border text-sm text-text-primary">
            {themeOptions.map((option) => {
              const Icon = option.icon
              const isActive = theme === option.value

              return (
                <li key={option.value}>
                  <button
                    type="button"
                    role="menuitemradio"
                    aria-checked={isActive}
                    onClick={() => {
                      setTheme(option.value)
                      setOpen(false)
                    }}
                    className="flex w-full items-center gap-3 px-3 py-2.5 text-left transition-colors hover:bg-bg-surface"
                  >
                    <span className="grid h-8 w-8 place-items-center rounded-lg bg-bg-surface text-text-secondary">
                      <Icon className="h-4 w-4" strokeWidth={1.7} />
                    </span>
                    <span className="flex-1">
                      <span className="block text-sm font-semibold">{option.label}</span>
                      {option.value === 'system' ? (
                        <span className="block text-xs text-text-muted">
                          Залежить від системи ({resolvedTheme === 'dark' ? 'темна' : 'світла'})
                        </span>
                      ) : null}
                    </span>
                    {isActive ? (
                      <Check className="h-4 w-4 text-primary" strokeWidth={2} />
                    ) : null}
                  </button>
                </li>
              )
            })}
          </ul>
        </div>
      ) : null}
    </div>
  )
}
