import { useState } from 'react'
import { ImageOff } from 'lucide-react'
import { tv } from 'tailwind-variants'
import { Spinner } from './Spinner'
import { resolveMediaSrc } from '@/lib/media'
import type { MediaConversionUrls } from '@/types/media'

const mediaThumbnail = tv({
  slots: {
    container: 'relative flex-shrink-0 overflow-hidden rounded-lg border border-line bg-surface',
    image: 'h-full w-full object-cover',
    fallback: 'flex h-full w-full items-center justify-center text-muted',
    processingBadge: 'absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-black/60',
  },
  variants: {
    size: {
      sm: { container: 'h-16 w-16' },
      md: { container: 'h-24 w-24' },
    },
  },
  defaultVariants: { size: 'md' },
})

export interface MediaThumbnailProps {
  media: MediaConversionUrls
  alt: string
  size?: 'sm' | 'md'
  href?: string
  className?: string
}

export function MediaThumbnail({ media, alt, size = 'md', href, className }: MediaThumbnailProps) {
  const [imgError, setImgError] = useState(false)
  const src = resolveMediaSrc(media, 'optimized_url')
  const { container, image, fallback, processingBadge } = mediaThumbnail({ size })

  const content = (
    <div className={container({ className })}>
      {src && !imgError ? (
        <img src={src} alt={alt} className={image()} onError={() => setImgError(true)} />
      ) : imgError ? (
        <div className={fallback()}>
          <ImageOff className="h-6 w-6" />
        </div>
      ) : (
        <div className={fallback()}>
          <Spinner size="sm" />
        </div>
      )}
      {media.is_processing ? (
        <span className={processingBadge()}>
          <Spinner size="xs" className="text-white" />
        </span>
      ) : null}
    </div>
  )

  if (href) {
    return (
      <a href={href} target="_blank" rel="noopener noreferrer" aria-label={alt}>
        {content}
      </a>
    )
  }

  return content
}
