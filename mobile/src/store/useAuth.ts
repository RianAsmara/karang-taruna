import * as SecureStore from 'expo-secure-store';
import { create } from 'zustand';

import { apiFetch, ApiError, setAuthToken } from '@/lib/api';

const STORAGE_KEY = 'rukunmuda.auth';

interface AuthUser {
  id: string;
  name: string;
  email: string;
}

interface StoredAuth {
  token: string;
  user: AuthUser;
}

interface AuthState {
  status: 'loading' | 'signedOut' | 'signedIn';
  user: AuthUser | null;
  bootstrap: () => Promise<void>;
  login: (email: string, password: string) => Promise<void>;
  register: (name: string, email: string, password: string, passwordConfirmation: string) => Promise<void>;
  logout: () => Promise<void>;
}

export const useAuth = create<AuthState>((set) => ({
  status: 'loading',
  user: null,

  bootstrap: async () => {
    const raw = await SecureStore.getItemAsync(STORAGE_KEY);
    if (!raw) {
      set({ status: 'signedOut' });
      return;
    }

    const stored: StoredAuth = JSON.parse(raw);
    setAuthToken(stored.token);
    set({ status: 'signedIn', user: stored.user });
  },

  login: async (email: string, password: string) => {
    const response = await apiFetch<{ token: string; user: AuthUser }>('/auth/login', {
      method: 'POST',
      body: { email, password, device_name: 'mobile' },
    });

    await SecureStore.setItemAsync(STORAGE_KEY, JSON.stringify(response));
    setAuthToken(response.token);
    set({ status: 'signedIn', user: response.user });
  },

  register: async (name: string, email: string, password: string, passwordConfirmation: string) => {
    const response = await apiFetch<{ token: string; user: AuthUser }>('/auth/register', {
      method: 'POST',
      body: { name, email, password, password_confirmation: passwordConfirmation, device_name: 'mobile' },
    });

    await SecureStore.setItemAsync(STORAGE_KEY, JSON.stringify(response));
    setAuthToken(response.token);
    set({ status: 'signedIn', user: response.user });
  },

  logout: async () => {
    try {
      await apiFetch('/auth/logout', { method: 'POST' });
    } catch (error) {
      // Token may already be invalid/expired server-side — still clear
      // local state so the user isn't stuck unable to sign out.
      if (!(error instanceof ApiError)) throw error;
    }

    await SecureStore.deleteItemAsync(STORAGE_KEY);
    setAuthToken(null);
    set({ status: 'signedOut', user: null });
  },
}));
