import { createContext, useContext, useMemo, useState, type ReactNode } from 'react';
import { Platform, useColorScheme } from 'react-native';

import { dark, light, type Theme } from './theme';

type Scheme = 'light' | 'dark';

/**
 * Web-only, testing/preview aid: `?scheme=light|dark` seeds the initial
 * override so a screenshot-comparison run isn't at the mercy of the
 * browser's OS-level prefers-color-scheme. No effect on iOS/Android, and
 * no effect on the app's actual behavior beyond picking the starting
 * scheme — useColorScheme()/the "◐ Gelap" toggle work exactly the same
 * either way.
 */
function initialSchemeOverride(): Scheme | null {
  if (Platform.OS !== 'web' || typeof window === 'undefined') return null;
  const value = new URLSearchParams(window.location.search).get('scheme');
  return value === 'light' || value === 'dark' ? value : null;
}

type ThemeContextValue = {
  theme: Theme;
  scheme: Scheme;
  /** Flips the effective scheme and pins it as a manual override — the "◐ Gelap" toggle on Transparansi (screen 07). */
  toggleScheme: () => void;
};

const ThemeContext = createContext<ThemeContextValue | null>(null);

export function ThemeProvider({ children }: { children: ReactNode }) {
  const systemScheme = useColorScheme();
  const [override, setOverride] = useState<Scheme | null>(initialSchemeOverride);

  const scheme: Scheme = override ?? (systemScheme === 'dark' ? 'dark' : 'light');
  // theme.ts's `as const` gives `dark` and `light` each their own literal
  // hex-string types, so the ternary doesn't structurally satisfy `Theme`
  // (= typeof light) on its own — both share the same shape, just
  // different literal values, which is exactly what Theme is for.
  const theme = (scheme === 'dark' ? dark : light) as Theme;

  const value = useMemo<ThemeContextValue>(
    () => ({
      theme,
      scheme,
      toggleScheme: () => setOverride(scheme === 'dark' ? 'light' : 'dark'),
    }),
    [theme, scheme],
  );

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useTheme(): ThemeContextValue {
  const ctx = useContext(ThemeContext);
  if (!ctx) throw new Error('useTheme must be used within ThemeProvider');
  return ctx;
}
