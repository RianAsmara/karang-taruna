import { useEffect, useState, type ReactNode } from 'react';
import {
  AccessibilityInfo,
  Animated,
  Modal,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

type Props = {
  visible: boolean;
  title: string;
  onClose: () => void;
  children: ReactNode;
};

export function BottomSheet({ visible, title, onClose, children }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const [translateY] = useState(() => new Animated.Value(300));

  useEffect(() => {
    let cancelled = false;
    AccessibilityInfo.isReduceMotionEnabled().then((reduceMotion) => {
      if (cancelled) return;
      Animated.timing(translateY, {
        toValue: visible ? 0 : 300,
        duration: reduceMotion ? 0 : visible ? theme.motion.sheetIn : theme.motion.sheetOut,
        useNativeDriver: true,
      }).start();
    });
    return () => {
      cancelled = true;
    };
  }, [visible, translateY, theme.motion.sheetIn, theme.motion.sheetOut]);

  return (
    <Modal visible={visible} transparent animationType="none" onRequestClose={onClose}>
      <View style={styles.root} accessibilityViewIsModal>
        <Pressable style={styles.backdrop} onPress={onClose} accessibilityLabel="Tutup" />
        <Animated.View style={[styles.panel, theme.shadow.md, { transform: [{ translateY }] }]}>
          <View style={styles.header}>
            <Text style={styles.title}>{title}</Text>
            <Pressable onPress={onClose} hitSlop={theme.hitSlop} accessibilityLabel="Tutup" accessibilityRole="button">
              <Text style={styles.close}>✕</Text>
            </Pressable>
          </View>
          {children}
        </Animated.View>
      </View>
    </Modal>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, justifyContent: 'flex-end' },
    backdrop: { ...StyleSheet.absoluteFill, backgroundColor: 'rgba(32,30,29,0.45)' },
    panel: {
      backgroundColor: theme.color.bg,
      borderTopWidth: 2,
      borderTopColor: theme.color.text,
      paddingBottom: theme.space.xxl,
    },
    header: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      paddingHorizontal: theme.layout.screenPadding,
      paddingVertical: theme.space.lg,
    },
    title: { fontFamily: 'Archivo_800ExtraBold', fontSize: 17, color: theme.color.text },
    close: { fontFamily: 'Archivo_800ExtraBold', fontSize: 17, color: theme.color.text },
  });
}
