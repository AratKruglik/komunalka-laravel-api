import type { User } from './auth'

export interface PageProps {
  auth: {
    user: User | null
  }
  flash: {
    success?: string
    error?: string
  }
}

export * from './entities'
export * from './auth'
export * from './api'
export * from './providers'
