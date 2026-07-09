import { useEffect, useRef, useState, type ReactNode } from 'react'
import { tv } from 'tailwind-variants'

const dropdownMenu = tv({
  slots: {
    container: 'relative',
    menu: [
      'absolute z-50 min-w-[180px] rounded-xl border border-border bg-bg-raised py-1.5 shadow-lg',
      'animate-in fade-in-0 zoom-in-95 duration-150',
    ],
    item: [
      'flex w-full items-center gap-2.5 px-3 py-2 text-left text-sm font-medium',
      'transition-colors focus-visible:outline-none',
    ],
  },
  variants: {
    position: {
      'bottom-right': { menu: 'right-0 top-full mt-1.5' },
      'bottom-left': { menu: 'left-0 top-full mt-1.5' },
      'top-right': { menu: 'bottom-full right-0 mb-1.5' },
      'top-left': { menu: 'bottom-full left-0 mb-1.5' },
    },
    itemTone: {
      default: {
        item: 'text-text-primary hover:bg-bg-surface focus-visible:bg-bg-surface',
      },
      danger: {
        item: 'text-error hover:bg-error/10 focus-visible:bg-error/10',
      },
    },
  },
  defaultVariants: {
    position: 'bottom-right',
    itemTone: 'default',
  },
})

export interface DropdownMenuItem {
  id: string
  label: string
  icon?: ReactNode
  tone?: 'default' | 'danger'
  disabled?: boolean
}

interface DropdownMenuProps {
  trigger: ReactNode
  items: DropdownMenuItem[]
  position?: 'bottom-right' | 'bottom-left' | 'top-right' | 'top-left'
  onSelect: (item: DropdownMenuItem) => void
  className?: string
}

export function DropdownMenu({
  trigger,
  items,
  position = 'bottom-right',
  onSelect,
  className,
}: DropdownMenuProps) {
  const [isOpen, setIsOpen] = useState(false)
  const menuRef = useRef<HTMLDivElement>(null)
  const styles = dropdownMenu({ position })

  useEffect(() => {
    if (!isOpen) return

    function handleClickOutside(event: MouseEvent) {
      if (menuRef.current && !menuRef.current.contains(event.target as Node)) {
        setIsOpen(false)
      }
    }

    function handleEscape(event: KeyboardEvent) {
      if (event.key === 'Escape') {
        setIsOpen(false)
      }
    }

    document.addEventListener('mousedown', handleClickOutside)
    document.addEventListener('keydown', handleEscape)

    return () => {
      document.removeEventListener('mousedown', handleClickOutside)
      document.removeEventListener('keydown', handleEscape)
    }
  }, [isOpen])

  const handleItemClick = (item: DropdownMenuItem) => {
    if (item.disabled) return
    setIsOpen(false)
    onSelect(item)
  }

  return (
    <div ref={menuRef} className={styles.container({ className })}>
      <div onClick={() => setIsOpen((prev) => !prev)}>{trigger}</div>

      {isOpen && (
        <div role="menu" aria-label="Меню дій" className={styles.menu()}>
          <ul className="flex flex-col">
            {items.map((item) => {
              const itemStyles = dropdownMenu({ itemTone: item.tone ?? 'default' })
              return (
                <li key={item.id}>
                  <button
                    type="button"
                    role="menuitem"
                    disabled={item.disabled}
                    className={itemStyles.item({
                      className: item.disabled ? 'cursor-not-allowed opacity-50' : '',
                    })}
                    onClick={() => handleItemClick(item)}
                  >
                    {item.icon && (
                      <span className="flex h-4 w-4 items-center justify-center">
                        {item.icon}
                      </span>
                    )}
                    {item.label}
                  </button>
                </li>
              )
            })}
          </ul>
        </div>
      )}
    </div>
  )
}
