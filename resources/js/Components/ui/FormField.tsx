import type { ReactNode } from 'react'
import { Label } from '@/Components/ui/form/Label'
import { FormMessage } from '@/Components/ui/form/FormMessage'

interface FormFieldProps {
    id: string
    label: string
    children: ReactNode
    required?: boolean
    error?: string
}

export function FormField({ id, label, required, error, children }: FormFieldProps) {
    return (
        <div className="space-y-2">
            <Label htmlFor={id} className="flex items-center gap-1">
                {label}
                {required ? <span className="text-error">*</span> : null}
            </Label>
            {children}
            {error ? <FormMessage variant="error">{error}</FormMessage> : null}
        </div>
    )
}
