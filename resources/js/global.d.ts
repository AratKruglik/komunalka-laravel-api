import type { RouteList } from 'ziggy-js'

declare global {
    function route(name: keyof RouteList, params?: string | number | Record<string, unknown> | (string | number)[], absolute?: boolean): string
    function route(): { current: (name?: string) => boolean }
}

interface ImportMetaEnv {
    readonly VITE_REVERB_APP_KEY?: string
    readonly VITE_REVERB_HOST?: string
    readonly VITE_REVERB_PORT?: string
    readonly VITE_REVERB_SCHEME?: string
}
