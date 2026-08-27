import * as SecureStore from 'expo-secure-store';

const MAX_RECENT = 5;

/**
 * Per-domain recent searches, capped at 5 (mobile-screens.md § 32).
 * Uses expo-secure-store (already linked, no new native dependency)
 * purely as small-string local storage — nothing sensitive is kept here.
 */
function storageKey(scope: string): string {
  return `rukunmuda.recent-search.${scope}`;
}

export async function getRecentSearches(scope: string): Promise<string[]> {
  const raw = await SecureStore.getItemAsync(storageKey(scope));
  if (!raw) return [];
  try {
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

export async function addRecentSearch(scope: string, query: string): Promise<string[]> {
  const trimmed = query.trim();
  if (!trimmed) return getRecentSearches(scope);

  const existing = await getRecentSearches(scope);
  const next = [trimmed, ...existing.filter((q) => q.toLowerCase() !== trimmed.toLowerCase())].slice(0, MAX_RECENT);
  await SecureStore.setItemAsync(storageKey(scope), JSON.stringify(next));
  return next;
}

export async function clearRecentSearches(scope: string): Promise<void> {
  await SecureStore.deleteItemAsync(storageKey(scope));
}
