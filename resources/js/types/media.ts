// Nested media conversion payload shared by UserResource.avatar, MeterResource.photo,
// and MeterReadingResource.photos[]. Intentionally kept snake_case: this object is a
// plain attribute *value* (not a JSON:API relationship), and `resources/js/lib/jsonapi.ts`
// only camelCases the top-level key of an attribute, never recursing into nested
// object/array values. Reading `.original_url` (not `.originalUrl`) here is correct,
// not a bug — see docs/plans/photo-upload-background-processing/02-development-plan-frontend.md
// Finding A.
export interface MediaConversionUrls {
  original_url: string
  optimized_url: string | null
  thumbnail_url: string | null
  is_processing: boolean
}
