import { useEffect, useRef, useState } from 'react'
import { router } from '@inertiajs/react'
import { Bell, ChevronDown, Menu } from 'lucide-react'
import { iconContainer, UserAvatar } from '../ui'
import { ThemeToggle } from './ThemeToggle'

export interface TopbarUser {
  name: string
  email: string
  avatarUrl?: string
}

interface AuthenticatedTopbarProps {
  title: string
  subtitle?: string
  notificationsCount?: number
  user: TopbarUser
  onMenuToggle?: () => void
  isSidebarOpen?: boolean
}

export function AuthenticatedTopbar({
  title,
  subtitle,
  notificationsCount = 0,
  user,
  onMenuToggle,
  isSidebarOpen,
}: AuthenticatedTopbarProps) {
  return (
    <header className="sticky top-0 z-20 border-b border-gray-200 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80 dark:border-slate-800 dark:bg-slate-900/90 supports-[backdrop-filter]:dark:bg-slate-900/80">
      <div className="flex w-full flex-col gap-2 px-3 py-2.5 sm:gap-3 sm:px-4 sm:py-3 md:flex-row md:items-center md:justify-between md:px-6 md:py-4">
        <div className="flex items-start gap-2 sm:gap-3">
          {onMenuToggle ? (
            <button
              type="button"
              onClick={onMenuToggle}
              className="inline-flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full border border-gray-200 text-gray-600 transition-colors hover:bg-gray-50 hover:text-gray-900 active:bg-gray-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-slate-50 dark:active:bg-slate-700 sm:h-10 sm:w-10 lg:hidden"
              aria-label="Відкрити меню"
              aria-expanded={isSidebarOpen}
            >
              <Menu className="h-4 w-4 sm:h-5 sm:w-5" />
            </button>
          ) : null}

          <div className="min-w-0 flex-1 leading-tight">
            <h1 className="text-lg font-semibold text-gray-900 dark:text-slate-50 sm:text-xl md:text-[22px]">
              {title}
            </h1>
            {subtitle ? (
              <p className="mt-0.5 text-xs font-medium text-gray-500 dark:text-slate-400 sm:mt-1 sm:text-sm md:text-[15px]">
                {subtitle}
              </p>
            ) : null}
          </div>
        </div>

        <div className="flex items-center justify-end gap-2 sm:gap-3">
          <ThemeToggle />
          <button
            type="button"
            className="relative inline-flex h-9 w-9 items-center justify-center rounded-full border border-gray-200 text-gray-500 transition-colors hover:bg-gray-50 hover:text-gray-900 active:bg-gray-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-slate-50 dark:active:bg-slate-700 sm:h-10 sm:w-10"
            aria-label="Повідомлення"
          >
            <Bell className="h-4 w-4 sm:h-5 sm:w-5" strokeWidth={1.6} />
            {notificationsCount > 0 ? (
              <span className="absolute right-0 top-0 inline-flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-badge-danger px-1 text-[10px] font-semibold leading-none text-white sm:right-0.5 sm:top-0.5">
                {notificationsCount > 9 ? '9+' : notificationsCount}
              </span>
            ) : null}
          </button>

          <UserMenu user={user} className="hidden md:block" />
        </div>
      </div>
    </header>
  )
}

interface UserMenuProps {
  user: TopbarUser
  variant?: 'default' | 'compact'
  className?: string
}

function UserMenu({ user, variant = 'default', className }: UserMenuProps) {
  const [open, setOpen] = useState(false)
  const menuRef = useRef<HTMLDivElement | null>(null)

  useEffect(() => {
    if (!open) {
      return
    }

    function handleClickOutside(event: MouseEvent) {
      if (
        menuRef.current &&
        !menuRef.current.contains(event.target as Node | null)
      ) {
        setOpen(false)
      }
    }

    function handleEscape(event: KeyboardEvent) {
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

  const menuItems: Array<{ id: string; label: string; tone?: 'danger'; path?: string }> = [
    { id: 'profile', label: 'Мій профіль', path: '/settings?tab=profile' },
    { id: 'settings', label: 'Налаштування', path: '/settings' },
    { id: 'logout', label: 'Вийти', tone: 'danger' },
  ]

  const handleMenuItemClick = (item: typeof menuItems[0]) => {
    setOpen(false)
    if (item.id === 'logout') {
      router.post('/logout')
      return
    }
    if (item.path) {
      router.visit(item.path)
    }
  }

  const isCompact = variant === 'compact'

  return (
    <div className={`relative ${className ?? ''}`} ref={menuRef}>
      <button
        type="button"
        onClick={() => setOpen((prev) => !prev)}
        aria-haspopup="menu"
        aria-expanded={open}
        className={`${
          isCompact
            ? `${iconContainer({ size: 'md' })} border border-gray-200 bg-white text-gray-700 shadow-sm hover:shadow dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100`
            : 'inline-flex items-center gap-3 rounded-full bg-white px-3 py-1.5 text-left shadow-sm transition-colors hover:shadow-md dark:bg-slate-900 dark:text-slate-100'
        } focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary`}
      >
        <UserAvatar src={user.avatarUrl} name={user.name} className={isCompact ? 'h-9 w-9' : ''} />
        {!isCompact ? (
          <>
            <span className="flex min-w-0 flex-col">
              <span className="truncate text-sm font-medium text-gray-900 dark:text-slate-50">
                {user.name}
              </span>
              <span className="hidden truncate text-xs text-gray-500 dark:text-slate-400 sm:inline">
                {user.email}
              </span>
            </span>
            <ChevronDown
              className={`h-4 w-4 text-gray-400 transition-transform ${
                open ? '-scale-y-100' : ''
              }`}
            />
          </>
        ) : null}
      </button>

      {open ? (
        <div
          role="menu"
          aria-label="Меню користувача"
          className="absolute right-0 top-full mt-2 w-56 rounded-xl border border-gray-100 bg-white shadow-[0_18px_40px_-20px_rgba(15,23,42,0.28)] dark:border-slate-800 dark:bg-slate-900"
        >
          <ul className="flex flex-col py-2">
            {menuItems.map((item) => (
              <li key={item.id}>
                <button
                  type="button"
                  role="menuitem"
                  className={`w-full px-4 py-2 text-left text-sm font-medium transition-colors focus-visible:outline-none ${
                    item.tone === 'danger'
                      ? 'text-red-600 hover:bg-red-50 focus-visible:bg-red-50 dark:text-red-300 dark:hover:bg-red-950/40 dark:focus-visible:bg-red-950/40'
                      : 'text-gray-900 hover:bg-gray-100 focus-visible:bg-gray-100 dark:text-slate-100 dark:hover:bg-slate-800 dark:focus-visible:bg-slate-800'
                  }`}
                  onClick={() => handleMenuItemClick(item)}
                >
                  {item.label}
                </button>
              </li>
            ))}
          </ul>
        </div>
      ) : null}
    </div>
  )
}
