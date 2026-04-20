# Inertia v3 + React 19 Conventions

## Stack

- **Inertia.js v3** — server-driven SPA routing
- **React 19** — functional components with hooks; never class components
- **TypeScript (strict)** — `.tsx` for components, `.ts` for utilities
- **Tailwind CSS v4** + **Tailwind Variants** — `tv()` for component variants
- **Ziggy v2** — `route()` helper for named routes
- Named exports only (no default exports)

## Component Rules

- Props typed via `interface` (not `type` alias)
- `tv()` for all variant-based styling — never inline conditional classnames
- Return type `ReactNode` on every component function
- No `any` — use `unknown` with narrowing if needed

## Shared Props

Access shared props (from `HandleInertiaRequests`) via `usePage()` — never re-pass them as component props.

## useForm

Use `useForm` from `@inertiajs/react` for all form handling. Form Request validation errors map automatically to `form.errors.field`.

## Deferred Props (v3)

Use `Inertia::defer(fn () => $data)` on the backend + `<Deferred data="key">` on frontend for lazy server data.
`Inertia::lazy()` / `LazyProp` are removed in v3 — use `Inertia::optional()`.

## WhenVisible

Use `<WhenVisible data="key">` for viewport-triggered lazy loading.

## Partial Reloads

`router.reload({ only: ['key'] })` to reload specific props without full page visit.

## Redirects

- Inertia redirect: `return redirect()->route('name')`
- Full/external redirect: `return Inertia::location(url)`

## SSR Caveats

- No `window` / `document` / `localStorage` at component top level — use `useEffect`
- Avoid non-deterministic render output (Date.now, Math.random) outside `useEffect`
- Inertia v3 SSR runs via `@inertiajs/vite` with no separate Node.js server

## Event Renames (v3 vs v2)

- `invalid` → `httpException`
- `exception` → `networkError`
- `router.cancel()` → `router.cancelAll()`

> For canonical code examples: activate skill `inertia-react-development`.
