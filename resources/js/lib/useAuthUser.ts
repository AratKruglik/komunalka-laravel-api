import { useMemo } from 'react'
import { usePage } from '@inertiajs/react'
import { denormalize, isJsonApiDocument } from '@/lib/jsonapi'
import type { User } from '@/types/auth'
import type { PageProps } from '@/types'

export function useAuthUser(): User | null {
    const { auth } = usePage<PageProps>().props

    return useMemo(() => {
        if (!auth.user) return null
        if (isJsonApiDocument(auth.user)) {
            return denormalize<User>(auth.user)
        }
        return auth.user as unknown as User
    }, [auth.user])
}
