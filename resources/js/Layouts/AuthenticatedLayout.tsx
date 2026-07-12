import { useEffect, useState, useMemo } from 'react'
import type { ReactNode } from 'react'
import { usePage, usePoll } from '@inertiajs/react'
import {
  AuthenticatedSidebar,
  type SidebarSection,
} from '@/Components/navigation/AuthenticatedSidebar'
import {
  AuthenticatedTopbar,
  type TopbarUser,
} from '@/Components/navigation/AuthenticatedTopbar'
import { Alert, AlertDescription } from '@/Components/ui'
import { HelpChatWidget } from '@/Components/help/HelpChatWidget'
import { resolveMediaSrc } from '@/lib/media'
import { useAuthUser } from '@/lib/useAuthUser'
import type { PageProps } from '@/types'

interface AuthenticatedLayoutProps {
  children: ReactNode
  pageTitle?: string
  pageSubtitle?: string
  notificationsCount?: number
  sidebarSections?: SidebarSection[]
  contentFillHeight?: boolean
}

export function AuthenticatedLayout({
  children,
  pageTitle = 'Мої адреси',
  pageSubtitle,
  notificationsCount = 0,
  sidebarSections,
  contentFillHeight = false,
}: AuthenticatedLayoutProps) {
  const { flash } = usePage<PageProps>().props
  const authUser = useAuthUser()
  const [isSidebarOpen, setIsSidebarOpen] = useState(false)

  const user = useMemo<TopbarUser>(() => {
    if (authUser) {
      const fullName = [authUser.firstName, authUser.lastName]
        .filter(Boolean)
        .join(' ') || authUser.username
      return {
        name: fullName,
        email: authUser.email,
        avatarUrl: resolveMediaSrc(authUser.avatar, 'thumbnail_url') ?? undefined,
        avatarIsProcessing: authUser.avatar?.is_processing ?? false,
      }
    }
    return {
      name: 'Користувач',
      email: '',
    }
  }, [authUser])

  const isAvatarProcessing = authUser?.avatar?.is_processing ?? false
  const avatarPoll = usePoll(4000, { only: ['auth'] })

  useEffect(() => {
    if (isAvatarProcessing) {
      avatarPoll.start()
    } else {
      avatarPoll.stop()
    }
    // `avatarPoll` intentionally omitted: usePoll returns a new wrapper object each
    // render around the same underlying stable poll controller, so including it here
    // would re-trigger start()/stop() on every render instead of only when processing
    // state actually changes.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isAvatarProcessing])

  const handleSidebarToggle = () => {
    setIsSidebarOpen((previous) => !previous)
  }

  const handleCloseSidebar = () => {
    setIsSidebarOpen(false)
  }

  useEffect(() => {
    if (!isSidebarOpen) {
      return
    }

    const handleKeyDown = (event: KeyboardEvent) => {
      if (event.key === 'Escape') {
        setIsSidebarOpen(false)
      }
    }

    window.addEventListener('keydown', handleKeyDown)
    const previousOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'

    return () => {
      window.removeEventListener('keydown', handleKeyDown)
      document.body.style.overflow = previousOverflow
    }
  }, [isSidebarOpen])

  return (
    <>
      <div
        className={
          contentFillHeight
            ? 'flex h-screen overflow-hidden bg-canvas text-foreground'
            : 'flex min-h-screen overflow-x-hidden bg-canvas text-foreground'
        }
      >
        <AuthenticatedSidebar sections={sidebarSections} />

        <div className="flex min-w-0 flex-1 flex-col">
          <AuthenticatedTopbar
            title={pageTitle}
            subtitle={pageSubtitle}
            notificationsCount={notificationsCount}
            user={user}
            onMenuToggle={handleSidebarToggle}
            isSidebarOpen={isSidebarOpen}
          />

          <main className={contentFillHeight ? 'min-h-0 flex-1 overflow-y-auto' : 'flex-1 overflow-y-auto'}>
            <div
              className={
                contentFillHeight
                  ? 'mx-auto flex h-full w-full max-w-screen-2xl flex-col px-3 py-4 sm:px-6 sm:py-5 lg:py-6'
                  : 'mx-auto flex min-h-full w-full max-w-screen-2xl flex-col px-3 py-4 sm:px-6 sm:py-5 lg:py-6'
              }
            >
              {flash.success ? (
                <Alert variant="success" className="mb-4">
                  <AlertDescription>{flash.success}</AlertDescription>
                </Alert>
              ) : null}
              {flash.error ? (
                <Alert variant="danger" className="mb-4">
                  <AlertDescription>{flash.error}</AlertDescription>
                </Alert>
              ) : null}
              {children}
            </div>
          </main>

          <footer className="border-t border-line bg-canvas">
            <div className="mx-auto flex w-full max-w-screen-2xl flex-col gap-3 px-3 py-4 text-sm text-subtext sm:flex-row sm:items-center sm:justify-between sm:gap-4 sm:px-6">
              <p className="text-xs sm:text-sm">&copy; {new Date().getFullYear()} Комуналка. Всі права захищені.</p>
              <div className="flex flex-wrap gap-3 text-xs sm:gap-4 sm:text-sm">
                <a href="#" className="transition-colors hover:text-foreground">
                  Умови використання
                </a>
                <a href="#" className="transition-colors hover:text-foreground">
                  Політика конфіденційності
                </a>
                <a href="#" className="transition-colors hover:text-foreground">
                  Контакти
                </a>
              </div>
            </div>
          </footer>
        </div>
      </div>

      {isSidebarOpen ? (
        <div className="fixed inset-0 z-40 flex lg:hidden" role="dialog" aria-modal="true">
          <div
            className="absolute inset-0 bg-black/40 backdrop-blur-[2px] transition-opacity"
            aria-hidden="true"
            onClick={handleCloseSidebar}
          />
          <div className="relative mr-auto flex h-full w-full max-w-[20rem] flex-1 sm:max-w-xs">
            <AuthenticatedSidebar
              sections={sidebarSections}
              variant="mobile"
              user={user}
              onClose={handleCloseSidebar}
              onNavigate={handleCloseSidebar}
            />
          </div>
        </div>
      ) : null}

      <HelpChatWidget />
    </>
  )
}
