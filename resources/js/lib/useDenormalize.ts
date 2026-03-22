import { useMemo } from 'react'
import { denormalize, denormalizeCollection, denormalizeAuto } from '@/lib/jsonapi'
import type { JsonApiDocument, JsonApiCollectionDocument } from '@/types/jsonapi'

export function useDenormalize<T>(document: JsonApiDocument): T {
    return useMemo(() => denormalize<T>(document), [document])
}

export function useDenormalizeCollection<T>(document: JsonApiCollectionDocument): T[] {
    return useMemo(() => denormalizeCollection<T>(document), [document])
}

export function useDenormalizeAuto<T>(value: JsonApiCollectionDocument | unknown[]): T[] {
    return useMemo(() => denormalizeAuto<T>(value), [value])
}
