import type {
    JsonApiCollectionDocument,
    JsonApiDocument,
    JsonApiRelationship,
    JsonApiResourceIdentifier,
    JsonApiResourceObject,
} from '@/types/jsonapi'

type IncludedMap = Map<string, JsonApiResourceObject>

function buildIncludedMap(included?: JsonApiResourceObject[]): IncludedMap {
    const map = new Map<string, JsonApiResourceObject>()
    if (!included) return map
    for (const resource of included) {
        map.set(`${resource.type}:${resource.id}`, resource)
    }
    return map
}

function coerceId(id: string): number | string {
    const num = Number(id)
    return Number.isNaN(num) ? id : num
}

function snakeToCamel(str: string): string {
    return str.replace(/_([a-z])/g, (_, letter: string) => letter.toUpperCase())
}

function camelCaseKeys(obj: Record<string, unknown>): Record<string, unknown> {
    const result: Record<string, unknown> = {}
    for (const [key, value] of Object.entries(obj)) {
        result[snakeToCamel(key)] = value
    }
    return result
}

function resolveRelationshipData(
    identifier: JsonApiResourceIdentifier,
    includedMap: IncludedMap,
    resolving: Set<string>,
): Record<string, unknown> | null {
    const key = `${identifier.type}:${identifier.id}`
    const resource = includedMap.get(key)
    if (!resource) {
        return { id: coerceId(identifier.id) }
    }
    return flattenResource(resource, includedMap, resolving)
}

function resolveRelationship(
    rel: JsonApiRelationship,
    includedMap: IncludedMap,
    resolving: Set<string>,
): Record<string, unknown> | Record<string, unknown>[] | null {
    if (rel.data === null) return null
    if (Array.isArray(rel.data)) {
        return rel.data.map((id) => resolveRelationshipData(id, includedMap, resolving)).filter(Boolean) as Record<string, unknown>[]
    }
    return resolveRelationshipData(rel.data, includedMap, resolving)
}

function flattenResource(
    resource: JsonApiResourceObject,
    includedMap: IncludedMap,
    resolving: Set<string> = new Set(),
): Record<string, unknown> {
    const resourceKey = `${resource.type}:${resource.id}`

    const result: Record<string, unknown> = {
        id: coerceId(resource.id),
        ...camelCaseKeys(resource.attributes ?? {}),
    }

    if (resource.relationships && !resolving.has(resourceKey)) {
        resolving.add(resourceKey)
        for (const [key, rel] of Object.entries(resource.relationships)) {
            result[snakeToCamel(key)] = resolveRelationship(rel, includedMap, resolving)
        }
        resolving.delete(resourceKey)
    }

    if (resource.meta) {
        for (const [key, value] of Object.entries(resource.meta)) {
            result[snakeToCamel(key)] = value
        }
    }

    return result
}

export function denormalize<T = Record<string, unknown>>(
    document: JsonApiDocument,
): T {
    const includedMap = buildIncludedMap(document.included)
    return flattenResource(document.data, includedMap) as T
}

export function denormalizeCollection<T = Record<string, unknown>>(
    document: JsonApiCollectionDocument,
): T[] {
    const includedMap = buildIncludedMap(document.included)
    return document.data.map((resource) => flattenResource(resource, includedMap)) as T[]
}

export function isJsonApiCollectionDocument(value: unknown): value is JsonApiCollectionDocument {
    return (
        typeof value === 'object' &&
        value !== null &&
        'data' in value &&
        Array.isArray((value as JsonApiCollectionDocument).data)
    )
}

export function denormalizeAuto<T = Record<string, unknown>>(
    value: JsonApiCollectionDocument | unknown[],
): T[] {
    if (Array.isArray(value)) return value as T[]
    if (isJsonApiCollectionDocument(value)) return denormalizeCollection<T>(value)
    return []
}
