import { Modal, StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';
import { Button } from './Button';

type Props = {
  visible: boolean;
  title: string;
  body: string;
  confirmLabel: string;
  onConfirm: () => void;
};

/**
 * A single-action, must-acknowledge notice — distinct from `Dialog`
 * (reserved for destructive/irreversible confirmations with a cancel
 * option). Use for things the user needs to know and act on, but isn't
 * being asked to approve or reject — e.g. a session that expired out
 * from under them. Generic by design so any such notice can reuse it.
 */
export function AlertModal({ visible, title, body, confirmLabel, onConfirm }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onConfirm}>
      <View style={styles.root} accessibilityViewIsModal>
        <View style={styles.backdrop} />
        <View style={[styles.card, theme.shadow.lg]}>
          <Text style={styles.title}>{title}</Text>
          <Text style={styles.body}>{body}</Text>
          <Button variant="primary" label={confirmLabel} onPress={onConfirm} compact block />
        </View>
      </View>
    </Modal>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { flex: 1, alignItems: 'center', justifyContent: 'center' },
    backdrop: { ...StyleSheet.absoluteFill, backgroundColor: 'rgba(32,30,29,0.45)' },
    card: {
      backgroundColor: theme.color.surface,
      margin: theme.space.xl,
      padding: theme.space.lg,
      width: '100%',
      maxWidth: 400,
    },
    title: { fontFamily: 'Archivo_800ExtraBold', fontSize: 17, color: theme.color.text, marginBottom: theme.space.sm },
    body: { fontFamily: 'Archivo_400Regular', fontSize: 14, lineHeight: 20, color: theme.color.textMuted, marginBottom: theme.space.lg },
  });
}
