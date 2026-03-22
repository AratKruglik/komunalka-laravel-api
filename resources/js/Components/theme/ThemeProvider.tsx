import { createContext, useCallback, useContext, useEffect, useMemo, useState } from 'react'
import type { ReactNode } from 'react'

type ThemePreference = 'light' | 'dark' | 'system'
type ResolvedTheme = 'light' | 'dark'

interface ThemeContextValue {
  theme: ThemePreference
  resolvedTheme: ResolvedTheme
  setTheme: (theme: ThemePreference) => void
}

const ThemeContext = createContext<ThemeContextValue | null>(null)

const THEME_COOKIE = 'theme'
const COOKIE_MAX_AGE_SECONDS = 60 * 60 * 24 * 365

function readThemeCookie(): ThemePreference {
  if (typeof document === 'undefined') {
    return 'light'
  }

  const value = document.cookie
    .split('; ')
    .find((entry) => entry.startsWith(`${THEME_COOKIE}=`))
    ?.split('=')[1]

  if (value === 'dark' || value === 'light' || value === 'system') {
    return value
  }

  return 'light'
}

function writeThemeCookie(theme: ThemePreference) {
  if (typeof document === 'undefined') {
    return
  }

  document.cookie = `${THEME_COOKIE}=${theme}; path=/; max-age=${COOKIE_MAX_AGE_SECONDS}; SameSite=Lax`
}

function systemPrefersDark() {
  if (typeof window === 'undefined') {
    return false
  }

  return window.matchMedia('(prefers-color-scheme: dark)').matches
}

function resolveTheme(theme: ThemePreference, prefersDark = systemPrefersDark()): ResolvedTheme {
  return theme === 'system' ? (prefersDark ? 'dark' : 'light') : theme
}

function applyTheme(theme: ThemePreference, prefersDark?: boolean): ResolvedTheme {
  const resolved = resolveTheme(theme, prefersDark)

  if (typeof document !== 'undefined') {
    document.documentElement.dataset.theme = resolved
    document.documentElement.style.colorScheme = resolved
  }

  return resolved
}

interface ThemeProviderProps {
  children: ReactNode
}

export function ThemeProvider({ children }: ThemeProviderProps) {
  const [theme, setThemeState] = useState<ThemePreference>(() => readThemeCookie())
  const [resolvedTheme, setResolvedTheme] = useState<ResolvedTheme>(() =>
    applyTheme(readThemeCookie()),
  )

  useEffect(() => {
    const initialTheme = readThemeCookie()
    setThemeState(initialTheme)
    setResolvedTheme(applyTheme(initialTheme))
  }, [])

  useEffect(() => {
    const nextResolved = applyTheme(theme)
    setResolvedTheme(nextResolved)
    writeThemeCookie(theme)
  }, [theme])

  useEffect(() => {
    if (typeof window === 'undefined') {
      return
    }

    const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
    const handleChange = (event: MediaQueryListEvent) => {
      if (theme === 'system') {
        setResolvedTheme(applyTheme('system', event.matches))
      }
    }

    mediaQuery.addEventListener('change', handleChange)
    return () => mediaQuery.removeEventListener('change', handleChange)
  }, [theme])

  const handleSetTheme = useCallback((nextTheme: ThemePreference) => {
    setThemeState(nextTheme)
  }, [])

  const value = useMemo<ThemeContextValue>(
    () => ({
      theme,
      resolvedTheme,
      setTheme: handleSetTheme,
    }),
    [resolvedTheme, theme, handleSetTheme],
  )

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>
}

export function useTheme() {
  const context = useContext(ThemeContext)

  if (!context) {
    throw new Error('useTheme must be used within a ThemeProvider')
  }

  return context
}
