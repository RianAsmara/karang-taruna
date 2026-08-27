import { router } from 'expo-router';
import { useEffect, useState } from 'react';

import { setUnauthorizedHandler } from '@/lib/api';
import { useAuth } from '@/store/useAuth';
import { AlertModal } from './AlertModal';

/**
 * Mounted once near the app root. Registers itself as the target for
 * `apiFetch`'s 401 handling (see `lib/api.ts`) — any query anywhere in
 * the app can trigger this without importing navigation or auth state
 * directly. Confirming clears the stale session and sends the user to
 * the login form, rather than leaving every screen to fail silently or
 * dead-end (see the RukunMuda mobile-build memory on this).
 */
export function SessionExpiredModal() {
  const [visible, setVisible] = useState(false);
  const logout = useAuth((state) => state.logout);

  useEffect(() => {
    setUnauthorizedHandler(() => setVisible(true));
    return () => setUnauthorizedHandler(null);
  }, []);

  return (
    <AlertModal
      visible={visible}
      title="Sesi berakhir"
      body="Sesi Anda telah berakhir. Silakan masuk kembali untuk melanjutkan."
      confirmLabel="Masuk kembali"
      onConfirm={() => {
        setVisible(false);
        logout().finally(() => router.replace('/login'));
      }}
    />
  );
}
