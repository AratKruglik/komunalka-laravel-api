import { Link, usePage } from '@inertiajs/react'
import {
  FilePlus,
  Gauge,
  HelpCircle,
  LayoutDashboard,
  LogOut,
  MapPin,
  Plug,
  Settings,
  User,
  X,
} from 'lucide-react'
import { Logo, UserAvatar } from '../ui'
import type { LucideIcon } from 'lucide-react'
import { router } from '@inertiajs/react'
import type { PageProps } from '@/types'

export interface SidebarItem {
  label: string
  href: string
  icon: LucideIcon
  badge?: string
  badgeTone?: 'info' | 'primary'
  exact?: boolean
}

export interface SidebarSection {
  heading: string
  items: SidebarItem[]
}

export interface SidebarUser {
  name: string
  email: string
  avatarUrl?: string
}

interface AuthenticatedSidebarProps {
  sections?: SidebarSection[]
  variant?: 'desktop' | 'mobile'
  user?: SidebarUser
  onClose?: () => void
  onNavigate?: () => void
}

const defaultSections: SidebarSection[] = [
  {
    heading: 'Головне меню',
    items: [
      {
        label: 'Дашборд',
        href: '/',
        icon: LayoutDashboard,
        exact: true,
      },
      {
        label: 'Мої адреси',
        href: '/addresses',
        icon: MapPin,
      },
      {
        label: 'Лічильники',
        href: '/meters',
        icon: Gauge,
      },
      {
        label: 'Внести показання',
        href: '/readings/create',
        icon: FilePlus,
      },
      {
        label: 'Провайдери',
        href: '/providers',
        icon: Plug,
      },
    ],
  },
  {
    heading: 'Налаштування',
    items: [
      {
        label: 'Налаштування',
        href: '/settings',
        icon: Settings,
      },
      {
        label: 'Допомога',
        href: '/help',
        icon: HelpCircle,
      },
    ],
  },
]

function isActive(url: string, href: string, exact?: boolean): boolean {
  if (exact) {
    return url === href
  }
  return url.startsWith(href)
}

export function AuthenticatedSidebar({
  sections = defaultSections,
  variant = 'desktop',
  user,
  onClose,
  onNavigate,
}: AuthenticatedSidebarProps) {
  const { url } = usePage<PageProps>()
  const isMobile = variant === 'mobile'

  const containerClasses = isMobile
    ? 'flex h-full w-full'
    : 'relative hidden w-60 shrink-0 lg:flex'

  const innerClasses = `flex h-full w-full flex-col border-r border-line bg-surface dark:bg-canvas ${
    isMobile ? 'shadow-2xl' : 'sticky top-0 min-h-full'
  }`

  return (
    <aside className={containerClasses}>
      <div className={innerClasses}>
        <div className="flex h-14 items-center gap-3 border-b border-line px-3 sm:h-16 sm:px-4 lg:h-[65px]">
          <Logo size="md" />
          {isMobile ? (
            <button
              type="button"
              onClick={onClose}
              className="ml-auto inline-flex h-8 w-8 items-center justify-center rounded-full border border-line text-muted transition-colors hover:bg-raised hover:text-foreground active:bg-raised focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary sm:h-9 sm:w-9"
              aria-label="Закрити меню"
            >
              <X className="h-4 w-4 sm:h-5 sm:w-5" />
            </button>
          ) : null}
        </div>

        <nav
          className={`flex-1 space-y-5 overflow-y-auto py-4 sm:space-y-6 sm:py-6 ${
            isMobile ? 'px-2' : 'px-0'
          }`}
        >
          {sections.map((section) => (
            <div key={section.heading} className="space-y-2 sm:space-y-3">
              <p className="px-3 text-[10px] font-semibold uppercase tracking-wider text-muted sm:px-4 sm:text-xs">
                {section.heading}
              </p>

              <div className="space-y-0.5 sm:space-y-1">
                {section.items.map((item) => (
                  <SidebarNavLink
                    key={item.label}
                    item={item}
                    currentUrl={url}
                    onNavigate={onNavigate}
                  />
                ))}
              </div>
            </div>
          ))}
        </nav>

        {user && isMobile ? (
          <MobileUserSection user={user} currentUrl={url} onNavigate={onNavigate} />
        ) : null}
      </div>
    </aside>
  )
}

function SidebarNavLink({
  item,
  currentUrl,
  onNavigate,
}: {
  item: SidebarItem
  currentUrl: string
  onNavigate?: () => void
}) {
  const Icon = item.icon
  const active = isActive(currentUrl, item.href, item.exact)

  return (
    <Link href={item.href} className="block" onClick={onNavigate}>
      <div
        className={`flex h-10 items-center gap-2.5 px-3 text-sm transition-colors sm:h-11 sm:gap-3 sm:px-4 sm:text-base lg:h-12 ${
          active
            ? 'bg-primary font-medium text-on-primary'
            : 'text-subtext hover:bg-raised hover:text-foreground active:bg-raised'
        }`}
      >
        <Icon className="h-4 w-4 flex-shrink-0 sm:h-[18px] sm:w-[18px]" />
        <span className="min-w-0 flex-1 truncate leading-5 sm:leading-6">{item.label}</span>
        {item.badge ? (
          <span
            className={`ml-auto inline-flex flex-shrink-0 items-center rounded-full px-1.5 py-0.5 text-[10px] font-semibold sm:px-2 sm:text-xs ${
              item.badgeTone === 'info'
                ? 'bg-info/15 text-info'
                : 'bg-primary/20 text-foreground'
            }`}
          >
            {item.badge}
          </span>
        ) : null}
      </div>
    </Link>
  )
}

function MobileUserSection({
  user,
  currentUrl,
  onNavigate,
}: {
  user: SidebarUser
  currentUrl: string
  onNavigate?: () => void
}) {
  const userMenuItems = [
    { icon: User, label: 'Мій профіль', href: '/settings?tab=profile' },
    { icon: Settings, label: 'Налаштування', href: '/settings' },
    { icon: LogOut, label: 'Вийти', href: '/logout', tone: 'danger' as const },
  ]

  return (
    <div className="border-t border-line bg-surface md:hidden">
      <div className="flex items-center gap-3 border-b border-line bg-raised px-3 py-3 sm:px-4 sm:py-4">
        <UserAvatar src={user.avatarUrl} name={user.name} size="md" />

        <div className="min-w-0 flex-1">
          <p className="truncate text-sm font-medium text-foreground sm:text-base">
            {user.name}
          </p>
          <p className="truncate text-xs text-muted sm:text-sm">
            {user.email}
          </p>
        </div>
      </div>

      <nav className="px-2 py-2">
        {userMenuItems.map((item) => {
          const Icon = item.icon
          const isDanger = item.tone === 'danger'
          const active = isActive(currentUrl, item.href) && !isDanger

          const handleClick = () => {
            onNavigate?.()
            if (isDanger) {
              router.post('/logout')
            }
          }

          if (isDanger) {
            return (
              <button
                key={item.href}
                type="button"
                onClick={handleClick}
                className="flex h-10 w-full items-center gap-2.5 px-3 text-sm transition-colors sm:h-11 sm:gap-3 sm:px-4 sm:text-base text-error hover:bg-error/10 active:bg-error/15"
              >
                <Icon className="h-4 w-4 flex-shrink-0 sm:h-[18px] sm:w-[18px]" />
                <span className="min-w-0 flex-1 truncate leading-5 sm:leading-6 text-left">
                  {item.label}
                </span>
              </button>
            )
          }

          return (
            <Link
              key={item.href}
              href={item.href}
              className="block"
              onClick={onNavigate}
            >
              <div
                className={`flex h-10 items-center gap-2.5 px-3 text-sm transition-colors sm:h-11 sm:gap-3 sm:px-4 sm:text-base ${
                  active
                    ? 'bg-primary font-medium text-on-primary'
                    : 'text-subtext hover:bg-raised hover:text-foreground active:bg-raised'
                }`}
              >
                <Icon className="h-4 w-4 flex-shrink-0 sm:h-[18px] sm:w-[18px]" />
                <span className="min-w-0 flex-1 truncate leading-5 sm:leading-6">
                  {item.label}
                </span>
              </div>
            </Link>
          )
        })}
      </nav>
    </div>
  )
}
