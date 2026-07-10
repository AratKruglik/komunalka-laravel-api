import { useState } from 'react'
import { tv, type VariantProps } from 'tailwind-variants'
import { Spinner } from './Spinner'

const AVATAR_COLORS = [
  'rgb(244, 63, 94)',
  'rgb(236, 72, 153)',
  'rgb(217, 70, 239)',
  'rgb(168, 85, 247)',
  'rgb(139, 92, 246)',
  'rgb(99, 102, 241)',
  'rgb(59, 130, 246)',
  'rgb(14, 165, 233)',
  'rgb(6, 182, 212)',
  'rgb(20, 184, 166)',
  'rgb(16, 185, 129)',
  'rgb(245, 158, 11)',
]

function getColorFromName(name: string): string {
  let hash = 5381
  for (let i = 0; i < name.length; i++) {
    hash = (hash * 33) ^ name.charCodeAt(i)
  }
  const index = Math.abs(hash) % AVATAR_COLORS.length
  return AVATAR_COLORS[index]
}

function getInitials(name: string): string {
  const words = name.trim().split(/\s+/).filter(Boolean)

  if (words.length === 0) return '?'
  if (words.length === 1) return words[0].charAt(0).toUpperCase()

  const first = words[0].charAt(0).toUpperCase()
  const last = words[words.length - 1].charAt(0).toUpperCase()
  return first + last
}

const userAvatar = tv({
  slots: {
    container: 'relative inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full select-none',
    image: 'h-full w-full object-cover',
    initials: 'font-semibold leading-none text-white',
  },
  variants: {
    size: {
      sm: { container: 'h-8 w-8', initials: 'text-xs' },
      md: { container: 'h-9 w-9 sm:h-10 sm:w-10', initials: 'text-sm' },
      lg: { container: 'h-11 w-11', initials: 'text-base' },
    },
  },
  defaultVariants: { size: 'md' },
})

type UserAvatarProps = VariantProps<typeof userAvatar> & {
  src?: string
  name: string
  className?: string
  isProcessing?: boolean
}

export function UserAvatar({ src, name, size, className, isProcessing = false }: UserAvatarProps) {
  const [imgError, setImgError] = useState(false)
  const [prevSrc, setPrevSrc] = useState(src)

  if (src !== prevSrc) {
    setPrevSrc(src)
    setImgError(false)
  }

  const showImage = src && !imgError
  const { container, image, initials } = userAvatar({ size })

  return (
    <div
      role="img"
      aria-label={name}
      className={container({ className })}
      style={showImage ? undefined : { backgroundColor: getColorFromName(name) }}
    >
      {showImage ? (
        <img
          src={src}
          alt=""
          className={image()}
          onError={() => setImgError(true)}
        />
      ) : (
        <span className={initials()}>
          {getInitials(name)}
        </span>
      )}
      {isProcessing ? (
        <span className="absolute inset-0 flex items-center justify-center rounded-full bg-black/40">
          <Spinner size="xs" className="text-white" />
        </span>
      ) : null}
    </div>
  )
}
