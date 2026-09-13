import { Platform } from 'react-native';

/**
 * `localhost` resolves to the device itself, not the dev machine — wrong
 * on the Android emulator (needs the special `10.0.2.2` alias) and on a
 * physical device (needs the dev machine's LAN IP). Override via
 * `EXPO_PUBLIC_API_URL` for those cases; this default only covers the
 * iOS simulator and web, where `localhost` does reach the dev machine.
 */
function resolveBaseUrl(): string {
  const fromEnv = process.env.EXPO_PUBLIC_API_URL;
  if (fromEnv) return fromEnv;

  const host = Platform.OS === 'android' ? '10.0.2.2' : 'localhost';
  return `http://${host}:8000/api/v1`;
}

export const API_BASE_URL = resolveBaseUrl();

/** Same Laravel app serves both the API and the web report pages — strip the `/api/v1` suffix to link to e.g. `/reports/{id}`. */
export const WEB_BASE_URL = API_BASE_URL.replace(/\/api\/v1$/, '');

let authToken: string | null = null;

export function setAuthToken(token: string | null) {
  authToken = token;
}

/**
 * Registered once by `SessionExpiredModal` near the app root — lets this
 * module (outside the React tree, used from plain `queryFn`s) trigger the
 * reusable "session expired" popup without importing screens/navigation.
 */
let onUnauthorized: (() => void) | null = null;

export function setUnauthorizedHandler(handler: (() => void) | null) {
  onUnauthorized = handler;
}

export class ApiError extends Error {
  status: number;
  errors: Record<string, string[]> | null;

  constructor(status: number, message: string, errors: Record<string, string[]> | null = null) {
    super(message);
    this.status = status;
    this.errors = errors;
  }
}

/** A 401 always means the token is missing/invalid/expired — except on
 * `/auth/logout` itself, whose caller already treats a failed logout as
 * "already signed out" and shouldn't also see the expired-session popup. */
function throwApiError(path: string, status: number, payload: { message?: string; errors?: Record<string, string[]> } | null): never {
  if (status === 401 && path !== '/auth/logout') {
    onUnauthorized?.();
  }

  throw new ApiError(status, payload?.message ?? 'Terjadi kesalahan. Coba lagi.', payload?.errors ?? null);
}

interface RequestOptions {
  method?: 'GET' | 'POST' | 'PATCH' | 'DELETE';
  body?: unknown;
}

/**
 * RN's `fetch()` has no default timeout, so a host that is routable but not
 * answering (dev machine bound to loopback, WiFi client isolation, a VPN
 * reshuffling routes) leaves the promise pending forever. The query never
 * settles, so it never reaches `isError`, so every screen sits on its loading
 * skeleton with nothing in logcat — the exact symptom that has cost two
 * debugging sessions. Fail fast instead and let the existing ErrorState render.
 */
const REQUEST_TIMEOUT_MS = 15000;

function timeoutSignal(): { signal: AbortSignal; done: () => void } {
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), REQUEST_TIMEOUT_MS);

  return { signal: controller.signal, done: () => clearTimeout(timer) };
}

/** An aborted fetch rejects with a bare DOMException; turn it into the same
 *  ApiError shape every caller already handles, with copy a user can act on. */
function asApiError(error: unknown): never {
  if (error instanceof ApiError) throw error;

  if (error instanceof Error && error.name === 'AbortError') {
    throw new ApiError(0, 'Sambungan ke server terputus. Periksa koneksi Anda, lalu coba lagi.');
  }

  throw new ApiError(0, 'Tidak bisa terhubung ke server. Periksa koneksi Anda, lalu coba lagi.');
}

export async function apiFetch<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const { signal, done } = timeoutSignal();

  let response: Response;

  try {
    response = await fetch(`${API_BASE_URL}${path}`, {
      method: options.method ?? 'GET',
      headers: {
        Accept: 'application/json',
        ...(options.body ? { 'Content-Type': 'application/json' } : {}),
        ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
      },
      body: options.body ? JSON.stringify(options.body) : undefined,
      signal,
    });
  } catch (error) {
    asApiError(error);
  } finally {
    done();
  }

  if (response.status === 204) {
    return undefined as T;
  }

  const payload = await response.json().catch(() => null);

  if (!response.ok) {
    throwApiError(path, response.status, payload);
  }

  return payload as T;
}

/**
 * Multipart upload — omits Content-Type so fetch sets the multipart
 * boundary itself; setting it manually on RN's FormData breaks the
 * boundary and the server sees an empty body.
 */
export async function apiUpload<T>(path: string, form: FormData): Promise<T> {
  // Same hang risk as apiFetch, but a real upload over a weak connection can
  // legitimately take far longer than a JSON call — so a much longer ceiling.
  const controller = new AbortController();
  const timer = setTimeout(() => controller.abort(), 120000);

  let response: Response;

  try {
    response = await fetch(`${API_BASE_URL}${path}`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
      },
      body: form,
      signal: controller.signal,
    });
  } catch (error) {
    asApiError(error);
  } finally {
    clearTimeout(timer);
  }

  const payload = await response.json().catch(() => null);

  if (!response.ok) {
    throwApiError(path, response.status, payload);
  }

  return payload as T;
}

/** Auth header can't be attached to a plain `<a>`/Linking URL, so downloads go through fetch + a raw request URL for callers (expo-file-system, expo-sharing) that need the bytes and the Authorization header together. */
export function authorizedDownloadUrl(path: string): { url: string; headers: Record<string, string> } {
  return {
    url: `${API_BASE_URL}${path}`,
    headers: {
      Accept: 'application/octet-stream',
      ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
    },
  };
}
