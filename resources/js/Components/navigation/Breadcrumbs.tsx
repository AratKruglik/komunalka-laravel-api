import { Link } from '@inertiajs/react'
import { ChevronRight } from 'lucide-react'

export interface BreadcrumbItem {
    label: string
    href?: string
}

interface BreadcrumbsProps {
    items: BreadcrumbItem[]
}

export function Breadcrumbs({ items }: BreadcrumbsProps) {
    return (
        <nav
            aria-label="Breadcrumb"
            className="flex flex-wrap items-center gap-2 text-sm text-text-muted"
        >
            {items.map((item, index) => {
                const isLast = index === items.length - 1

                return (
                    <span key={item.label} className="flex items-center gap-2">
                        {item.href && !isLast ? (
                            <Link
                                href={item.href}
                                className="transition-colors hover:text-text-secondary"
                            >
                                {item.label}
                            </Link>
                        ) : (
                            <span
                                className={isLast ? 'font-medium text-text-secondary' : undefined}
                            >
                                {item.label}
                            </span>
                        )}
                        {!isLast ? <ChevronRight className="h-4 w-4" /> : null}
                    </span>
                )
            })}
        </nav>
    )
}
