export interface SuggestedChipsProps {
  items: string[]
  onSelect: (value: string) => void
  disabled?: boolean
}

export function SuggestedChips({ items, onSelect, disabled = false }: SuggestedChipsProps) {
  if (items.length === 0) {
    return null
  }

  return (
    <div className="flex flex-wrap gap-2">
      {items.map((item) => (
        <button
          key={item}
          type="button"
          disabled={disabled}
          onClick={() => onSelect(item)}
          className="rounded-full border border-line bg-canvas px-3.5 py-1.5 text-[13px] text-subtext transition-colors hover:border-primary hover:text-foreground disabled:pointer-events-none disabled:opacity-60"
        >
          {item}
        </button>
      ))}
    </div>
  )
}
