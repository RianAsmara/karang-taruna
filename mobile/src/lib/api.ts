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

export class ApiError extends Error {
  status: number;
  errors: Record<string, string[]> | null;

  constructor(status: number, message: string, errors: Record<string, string[]> | null = null) {
    super(message);
    this.status = status;
    this.errors = errors;
  }
}

interface RequestOptions {
  method?: 'GET' | 'POST' | 'PATCH' | 'DELETE';
  body?: unknown;
}

export async function apiFetch<T>(path: string, options: RequestOptions = {}): Promise<T> {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    method: options.method ?? 'GET',
    headers: {
      Accept: 'application/json',
      ...(options.body ? { 'Content-Type': 'application/json' } : {}),
      ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
    },
    body: options.body ? JSON.stringify(options.body) : undefined,
  });

  if (response.status === 204) {
    return undefined as T;
  }

  const payload = await response.json().catch(() => null);

  if (!response.ok) {
    throw new ApiError(
      response.status,
      payload?.message ?? 'Terjadi kesalahan. Coba lagi.',
      payload?.errors ?? null,
    );
  }

  return payload as T;
}
