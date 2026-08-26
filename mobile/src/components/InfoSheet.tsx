import { StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { BottomSheet } from './BottomSheet';
import { Button } from './Button';

/**
 * The bottom-sheet counterpart of BelumTersedia — for the two controls
 * whose stub was specified as a sheet rather than a pushed screen: the
 * Home header org switcher and Notifikasi's "Ikut memilih".
 */
export function InfoSheet({
  visible,
  title,
  body,
  onClose,
}: {
  visible: boolean;
  title: string;
  body: string;
  onClose: () => void;
}) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  return (
    <BottomSheet visible={visible} title={title} onClose={onClose}>
      <View style={styles.body}>
        <Text style={styles.text}>{body}</Text>
        <Button variant="secondary" label="Tutup" onPress={onClose} block />
      </View>
    </BottomSheet>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    body: { paddingHorizontal: theme.layout.screenPadding, gap: theme.space.lg },
    text: { fontFamily: 'Archivo_400Regular', fontSize: 14, lineHeight: 21, color: theme.color.textMuted },
  });
}
