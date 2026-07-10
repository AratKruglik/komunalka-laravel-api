import type { MediaConversionUrls } from '@/types/media'

const HEIC_EXTENSION_PATTERN = /\.hei[cf]$/i

function isHeicUrl(url: string): boolean {
  try {
    return HEIC_EXTENSION_PATTERN.test(new URL(url).pathname)
  } catch {
    return HEIC_EXTENSION_PATTERN.test(url)
  }
}

/**
 * Resolves the best displayable image URL for a `MediaConversionUrls` payload:
 * prefer the given conversion, fall back to `original_url` only when it's not
 * still processing (a not-yet-generated conversion has a `null` URL) and the
 * original isn't a HEIC/HEIF file (browsers can't render those directly),
 * otherwise `null` so callers can show a processing/placeholder state instead
 * of a broken `<img>`.
 */
export function resolveMediaSrc(
  media: MediaConversionUrls | null | undefined,
  preferredConversion: 'optimized_url' | 'thumbnail_url' = 'optimized_url',
): string | null {
  if (!media) return null
  if (media[preferredConversion]) return media[preferredConversion]
  if (media.is_processing) return null
  if (isHeicUrl(media.original_url)) return null
  return media.original_url
}
