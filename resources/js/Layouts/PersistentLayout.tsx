import type { ReactNode } from 'react'
import { HelpChatWidget } from '@/Components/help/HelpChatWidget'

export interface PersistentLayoutProps {
  children: ReactNode
}

export function PersistentLayout({ children }: PersistentLayoutProps) {
  return (
    <>
      {children}
      <HelpChatWidget />
    </>
  )
}
