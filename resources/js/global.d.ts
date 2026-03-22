import type { RouteList } from 'ziggy-js'

declare global {
    function route(name: keyof RouteList, params?: string | number | Record<string, unknown> | (string | number)[], absolute?: boolean): string
    function route(): { current: (name?: string) => boolean }
}
