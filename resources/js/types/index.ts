import type { JsonApiDocument } from './jsonapi'

export interface PageProps {
  [key: string]: unknown
  auth: {
    user: JsonApiDocument | null
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
export * from './jsonapi'
export * from './media'
