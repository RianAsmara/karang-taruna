import { createContext, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { Platform, useColorScheme } from 'react-native';

import { useAuth } from '@/store/useAuth';

import { loadCachedTheme, revalidateThemeInBackground, type RemoteTheme } from './remoteTheme';
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
  /** Flips the effective scheme and pins it as a manual override — the theme row on Profil & organisasi's "Akun" section. */
  toggleScheme: () => void;
  /** The org's logo URLs, if a theme is set — null falls back to the wordmark (see Home's header). */
  logo: RemoteTheme['logo'] | null;
};

const ThemeContext = createContext<ThemeContextValue | null>(null);

export function ThemeProvider({ children }: { children: ReactNode }) {
  const systemScheme = useColorScheme();
  const [override, setOverride] = useState<Scheme | null>(initialSchemeOverride);
  const [remote, setRemote] = useState<RemoteTheme | null>(null);
  const authStatus = useAuth((state) => state.status);

  // Cache read is fast (local storage) and never blocks first paint — the
  // app renders with the binary default immediately, and this only ever
  // upgrades the running screen a moment later if an org theme is cached.
  // Safe before login too (an empty/absent cache is just a no-op).
  useEffect(() => {
    loadCachedTheme().then(setRemote);
  }, []);

  // Revalidation hits an authenticated, org-scoped endpoint — only once
  // signed in, or a guest on the login screen would 401 and trip the
  // reusable "session expired" popup for a session that never existed.
  // It only updates the *cache* for next launch, never this session's
  // live colors (spec: "a theme change mid-session applies on next
  // launch, not live").
  useEffect(() => {
    if (authStatus === 'signedIn') void revalidateThemeInBackground();
  }, [authStatus]);

  const scheme: Scheme = override ?? (systemScheme === 'dark' ? 'dark' : 'light');
  // theme.ts's `as const` gives `dark` and `light` each their own literal
  // hex-string types, so the ternary doesn't structurally satisfy `Theme`
  // (= typeof light) on its own — both share the same shape, just
  // different literal values, which is exactly what Theme is for.
  const baseTheme = (scheme === 'dark' ? dark : light) as Theme;
  const remoteColors = remote?.color[scheme];

  const value = useMemo<ThemeContextValue>(() => {
    // Only the six known keys ever come from the server; every other
    // token (neutrals, income/balance/expense, spacing, radius…) stays
    // exactly theme.ts's binary default no matter what the response
    // contains.
    const theme: Theme = remoteColors ? ({ ...baseTheme, color: { ...baseTheme.color, ...remoteColors } } as Theme) : baseTheme;

    return {
      theme,
      scheme,
      toggleScheme: () => setOverride(scheme === 'dark' ? 'light' : 'dark'),
      logo: remote?.logo ?? null,
    };
  }, [baseTheme, remoteColors, scheme, remote]);

  return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useTheme(): ThemeContextValue {
  const ctx = useContext(ThemeContext);
  if (!ctx) throw new Error('useTheme must be used within ThemeProvider');
  return ctx;
}
