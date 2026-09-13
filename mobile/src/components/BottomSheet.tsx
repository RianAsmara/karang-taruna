import { useEffect, useState, type ReactNode } from 'react';
import {
  AccessibilityInfo,
  Animated,
  KeyboardAvoidingView,
  Modal,
  Platform,
  Pressable,
  ScrollView,
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
      {/* A sheet anchored to the bottom is exactly what the keyboard covers,
          so every sheet that holds an input (Pinjam barang, Catat transaksi)
          needs to lift with it — otherwise the user can't see what they're
          typing. The inner ScrollView keeps a tall sheet reachable once the
          keyboard has eaten most of the screen. */}
      <KeyboardAvoidingView
        style={styles.root}
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        accessibilityViewIsModal
      >
        <Pressable style={styles.backdrop} onPress={onClose} accessibilityLabel="Tutup" />
        <Animated.View style={[styles.panel, theme.shadow.md, { transform: [{ translateY }] }]}>
          <View style={styles.header}>
            <Text style={styles.title}>{title}</Text>
            <Pressable onPress={onClose} hitSlop={theme.hitSlop} accessibilityLabel="Tutup" accessibilityRole="button">
              <Text style={styles.close}>✕</Text>
            </Pressable>
          </View>
          <ScrollView
            keyboardShouldPersistTaps="handled"
            keyboardDismissMode="on-drag"
            contentContainerStyle={styles.content}
          >
            {children}
          </ScrollView>
        </Animated.View>
      </KeyboardAvoidingView>
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
      // Never taller than the space left above the keyboard.
      maxHeight: '90%',
    },
    header: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      paddingHorizontal: theme.layout.screenPadding,
      paddingVertical: theme.space.lg,
    },
    content: { flexGrow: 0 },
    title: { fontFamily: 'Archivo_800ExtraBold', fontSize: 17, color: theme.color.text },
    close: { fontFamily: 'Archivo_800ExtraBold', fontSize: 17, color: theme.color.text },
  });
}
