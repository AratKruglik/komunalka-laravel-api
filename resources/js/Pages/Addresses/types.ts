import type { PageProps } from '@/types'
import type { JsonApiCollectionDocument, JsonApiDocument } from '@/types/jsonapi'

export interface IndexPageProps extends PageProps {
    addresses: JsonApiCollectionDocument
}

export interface CreatePageProps extends PageProps {
    regions: JsonApiCollectionDocument
    addressTypes: JsonApiCollectionDocument
}

export interface EditPageProps extends PageProps {
    address: JsonApiDocument
    regions: JsonApiCollectionDocument
    addressTypes: JsonApiCollectionDocument
}
