import * as SecureStore from 'expo-secure-store';

import { apiFetch, ApiError } from '@/lib/api';

const CACHE_KEY = 'rukunmuda.theme.current';

export type RemoteColorSet = {
  accent: string;
  accent200: string;
  accent700: string;
  accent800: string;
  onAccent: string;
  onInk: string;
};

export type RemoteTheme = {
  primary: string;
  logo: { mark: string; icon: string; mono: string };
  color: { light: RemoteColorSet; dark: RemoteColorSet };
};

const COLOR_KEYS: (keyof RemoteColorSet)[] = ['accent', 'accent200', 'accent700', 'accent800', 'onAccent', 'onInk'];

function isColorSet(value: unknown): value is RemoteColorSet {
  if (typeof value !== 'object' || value === null) return false;
  const record = value as Record<string, unknown>;
  return COLOR_KEYS.every((key) => typeof record[key] === 'string');
}

/**
 * The only thing mobile trusts from the server response — unknown keys are
 * ignored, a missing/malformed shape at any level fails the whole thing
 * (never a half-themed UI). No color math happens here or anywhere on
 * device; every value is used exactly as the server derived it.
 */
function isValidRemoteTheme(value: unknown): value is RemoteTheme {
  if (typeof value !== 'object' || value === null) return false;
  const record = value as Record<string, unknown>;

  if (typeof record.primary !== 'string') return false;

  const logo = record.logo as Record<string, unknown> | undefined;
  if (typeof logo !== 'object' || logo === null) return false;
  if (typeof logo.mark !== 'string' || typeof logo.icon !== 'string' || typeof logo.mono !== 'string') return false;

  const color = record.color as Record<string, unknown> | undefined;
  if (typeof color !== 'object' || color === null) return false;

  return isColorSet(color.light) && isColorSet(color.dark);
}

/** Fast, synchronous-ish local read — this is what actually renders at launch. */
export async function loadCachedTheme(): Promise<RemoteTheme | null> {
  try {
    const raw = await SecureStore.getItemAsync(CACHE_KEY);
    if (!raw) return null;

    const parsed: unknown = JSON.parse(raw);
    if (!isValidRemoteTheme(parsed)) {
      console.warn('[theme] Cached theme is malformed, ignoring.');
      return null;
    }

    return parsed;
  } catch (err) {
    console.warn('[theme] Failed to read cached theme, falling back to default.', err);
    return null;
  }
}

/**
 * Fetches the org's current theme and, if it validates, persists it as the
 * cache for the *next* launch. Never applied to the running session — "a
 * theme change mid-session applies on next launch, not live" (spec). A
 * missing theme (404, org hasn't set one) or any network/shape failure is
 * swallowed silently and logged — this must never surface an error to the
 * user or block anything.
 */
export async function revalidateThemeInBackground(): Promise<void> {
  try {
    const response = await apiFetch<{ data: unknown }>('/organizations/current/theme');

    if (!isValidRemoteTheme(response.data)) {
      console.warn('[theme] Server theme response is malformed, keeping previous cache.');
      return;
    }

    await SecureStore.setItemAsync(CACHE_KEY, JSON.stringify(response.data));
  } catch (err) {
    if (err instanceof ApiError && err.status === 404) {
      // No theme set for this org yet — not an error, just nothing to cache.
      return;
    }

    console.warn('[theme] Failed to revalidate theme from server.', err);
  }
}
