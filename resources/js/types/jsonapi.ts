export interface JsonApiResourceIdentifier {
    id: string
    type: string
}

export interface JsonApiResourceObject extends JsonApiResourceIdentifier {
    attributes?: Record<string, unknown>
    relationships?: Record<string, JsonApiRelationship>
    links?: Record<string, string>
    meta?: Record<string, unknown>
}

export interface JsonApiRelationship {
    data: JsonApiResourceIdentifier | JsonApiResourceIdentifier[] | null
}

export interface JsonApiDocument {
    data: JsonApiResourceObject
    included?: JsonApiResourceObject[]
    jsonapi?: Record<string, unknown>
}

export interface JsonApiCollectionDocument {
    data: JsonApiResourceObject[]
    included?: JsonApiResourceObject[]
    jsonapi?: Record<string, unknown>
    links?: Record<string, string | null>
    meta?: Record<string, unknown>
}
