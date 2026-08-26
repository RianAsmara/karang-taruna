import { createContext, useCallback, useContext, useMemo, useRef, useState, type ReactNode } from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

type ToastOptions = { actionLabel?: string; onAction?: () => void };
type ToastState = { message: string } & ToastOptions;

type ToastContextValue = {
  showToast: (message: string, options?: ToastOptions) => void;
};

const ToastContext = createContext<ToastContextValue | null>(null);

export function ToastProvider({ children }: { children: ReactNode }) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const [toast, setToast] = useState<ToastState | null>(null);
  const timer = useRef<ReturnType<typeof setTimeout> | null>(null);

  const showToast = useCallback(
    (message: string, options?: ToastOptions) => {
      if (timer.current) clearTimeout(timer.current);
      setToast({ message, ...options });
      const duration = options?.actionLabel ? theme.motion.toastUndo : theme.motion.toast;
      timer.current = setTimeout(() => setToast(null), duration);
    },
    [theme.motion.toast, theme.motion.toastUndo],
  );

  const value = useMemo(() => ({ showToast }), [showToast]);

  return (
    <ToastContext.Provider value={value}>
      {children}
      {toast ? (
        <View style={styles.wrap} pointerEvents="box-none">
          <View style={styles.toast} accessibilityLiveRegion="polite">
            <Text style={styles.message} numberOfLines={2}>
              {toast.message}
            </Text>
            {toast.actionLabel ? (
              <Pressable
                onPress={() => {
                  toast.onAction?.();
                  setToast(null);
                }}
                hitSlop={theme.hitSlop}
              >
                <Text style={styles.action}>{toast.actionLabel}</Text>
              </Pressable>
            ) : null}
          </View>
        </View>
      ) : null}
    </ToastContext.Provider>
  );
}

export function useToast(): ToastContextValue {
  const ctx = useContext(ToastContext);
  if (!ctx) throw new Error('useToast must be used within ToastProvider');
  return ctx;
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    wrap: { position: 'absolute', left: 0, right: 0, bottom: 74, alignItems: 'center' },
    toast: {
      backgroundColor: theme.color.text,
      marginHorizontal: theme.space.md,
      paddingVertical: theme.space.md,
      paddingHorizontal: theme.space.lg,
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      gap: theme.space.md,
      ...theme.shadow.sm,
    },
    message: { fontFamily: 'Archivo_600SemiBold', fontSize: 13.5, color: theme.color.onInk, flexShrink: 1 },
    action: { fontFamily: 'Archivo_800ExtraBold', fontSize: 13, color: theme.color.onInk, textDecorationLine: 'underline' },
  });
}
