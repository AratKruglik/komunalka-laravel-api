interface LogoProps {
  size?: 'sm' | 'md' | 'lg'
  showText?: boolean
  className?: string
}

const sizeMap = {
  sm: {
    icon: 'w-5 h-6',
    text: 'text-lg',
    gap: 'gap-1.5',
  },
  md: {
    icon: 'w-6 h-7',
    text: 'text-2xl',
    gap: 'gap-2',
  },
  lg: {
    icon: 'w-8 h-10',
    text: 'text-3xl',
    gap: 'gap-2.5',
  },
} as const

export function Logo({ size = 'md', showText = true, className = '' }: LogoProps) {
  const sizeConfig = sizeMap[size]

  return (
    <div
      className={['flex items-center font-semibold text-text-primary', sizeConfig.gap, className]
        .filter(Boolean)
        .join(' ')}
    >
      <svg
        className={sizeConfig.icon}
        viewBox="0 0 24 24"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
      >
        <path
          d="M13 2L3 14h8l-1 8 10-12h-8l1-8z"
          className="fill-primary stroke-primary-stroke drop-shadow"
          strokeWidth="1"
          strokeLinecap="round"
          strokeLinejoin="round"
        />
      </svg>

      {showText ? (
        <span className={['font-bold', sizeConfig.text].join(' ')}>
          Комуналка
        </span>
      ) : null}
    </div>
  )
}
