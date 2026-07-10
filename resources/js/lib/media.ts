import type { MediaConversionUrls } from '@/types/media'

/**
 * Resolves the best displayable image URL for a `MediaConversionUrls` payload:
 * prefer the given conversion, fall back to `original_url` only when it's not
 * still processing (a not-yet-generated conversion has a `null` URL), otherwise
 * `null` so callers can show a processing/placeholder state instead of a broken `<img>`.
 */
export function resolveMediaSrc(
  media: MediaConversionUrls | null | undefined,
  preferredConversion: 'optimized_url' | 'thumbnail_url' = 'optimized_url',
): string | null {
  if (!media) return null
  if (media[preferredConversion]) return media[preferredConversion]
  return media.is_processing ? null : media.original_url
}
