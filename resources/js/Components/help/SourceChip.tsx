import { Link } from '@inertiajs/react'
import { ExternalLink, FileText } from 'lucide-react'

export interface SourceChipProps {
  source: {
    label: string
    path: string
  }
}

export function SourceChip({ source }: SourceChipProps) {
  return (
    <Link
      href={source.path}
      className="flex w-full items-center gap-2.5 rounded-[10px] border border-line bg-canvas px-3.5 py-2.5 text-foreground transition-colors hover:border-primary"
    >
      <span className="flex h-[30px] w-[30px] shrink-0 items-center justify-center rounded-lg bg-surface text-subtext">
        <FileText className="h-[15px] w-[15px]" />
      </span>
      <span className="min-w-0 flex-1">
        <span className="block truncate text-[13px] font-semibold leading-tight">{source.label}</span>
        <span className="mt-0.5 block truncate text-xs text-muted">{source.path}</span>
      </span>
      <ExternalLink className="h-[15px] w-[15px] shrink-0 text-muted" />
    </Link>
  )
}
