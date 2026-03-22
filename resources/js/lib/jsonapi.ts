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

function resolveRelationshipData(
    identifier: JsonApiResourceIdentifier,
    includedMap: IncludedMap,
): Record<string, unknown> | null {
    const resource = includedMap.get(`${identifier.type}:${identifier.id}`)
    if (!resource) {
        return { id: Number(identifier.id) }
    }
    return flattenResource(resource, includedMap)
}

function resolveRelationship(
    rel: JsonApiRelationship,
    includedMap: IncludedMap,
): unknown {
    if (rel.data === null) return null
    if (Array.isArray(rel.data)) {
        return rel.data.map((id) => resolveRelationshipData(id, includedMap))
    }
    return resolveRelationshipData(rel.data, includedMap)
}

function flattenResource(
    resource: JsonApiResourceObject,
    includedMap: IncludedMap,
): Record<string, unknown> {
    const result: Record<string, unknown> = {
        id: Number(resource.id),
        ...(resource.attributes ?? {}),
    }

    if (resource.relationships) {
        for (const [key, rel] of Object.entries(resource.relationships)) {
            result[key] = resolveRelationship(rel, includedMap)
        }
    }

    if (resource.meta) {
        for (const [key, value] of Object.entries(resource.meta)) {
            result[key] = value
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

export function isJsonApiDocument(value: unknown): value is JsonApiDocument {
    return (
        typeof value === 'object' &&
        value !== null &&
        'data' in value &&
        typeof (value as JsonApiDocument).data === 'object' &&
        !Array.isArray((value as JsonApiDocument).data) &&
        'type' in ((value as JsonApiDocument).data ?? {})
    )
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
    value: JsonApiDocument | JsonApiCollectionDocument | unknown[] | { data: unknown[] },
): T[] {
    if (Array.isArray(value)) return value as T[]
    if (isJsonApiCollectionDocument(value)) return denormalizeCollection<T>(value)
    if ('data' in value && Array.isArray((value as { data: unknown[] }).data)) {
        return (value as { data: T[] }).data
    }
    return []
}
