import { type ReactNode } from 'react';

interface GuestLayoutProps {
    children: ReactNode;
}

export function GuestLayout({ children }: GuestLayoutProps) {
    return (
        <div className="min-h-screen bg-bg-primary px-4 py-8 text-text-primary sm:py-12">
            <div className="mx-auto flex w-full max-w-[1100px] flex-col gap-8 sm:gap-10">
                <div className="flex flex-1 items-center justify-center">
                    {children}
                </div>
            </div>
        </div>
    );
}
