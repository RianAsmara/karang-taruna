import { Pressable, StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

type Props = {
  title: string;
  action?: string;
  onAction?: () => void;
  /** Header text tinted accent — used by Notifikasi's "Perlu tindakan" group. */
  accent?: boolean;
  /** On a color.accent card background (e.g. Home's "Perlu tindakan" card) — onAccent at opacity 0.9 instead of textMuted. */
  onAccentBackground?: boolean;
};

export function SectionHeader({ title, action, onAction, accent, onAccentBackground }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  return (
    <View style={styles.row}>
      <Text
        style={[styles.title, accent && styles.titleAccent, onAccentBackground && styles.titleOnAccent]}
      >
        {title}
      </Text>
      {action ? (
        <Pressable onPress={onAction} hitSlop={theme.hitSlop} android_ripple={null}>
          <Text style={styles.action}>{action}</Text>
        </Pressable>
      ) : null}
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    row: {
      flexDirection: 'row',
      alignItems: 'center',
      justifyContent: 'space-between',
      paddingHorizontal: theme.layout.screenPadding,
      paddingTop: theme.space.xl - 4,
      paddingBottom: theme.space.sm + 2,
    },
    title: {
      fontFamily: 'Archivo_800ExtraBold',
      fontSize: 11,
      lineHeight: 14,
      letterSpacing: 1.0,
      textTransform: 'uppercase',
      color: theme.color.textMuted,
    },
    titleAccent: { color: theme.color.accent },
    titleOnAccent: { color: theme.color.onAccent, opacity: 0.9 },
    action: { fontFamily: 'Archivo_800ExtraBold', fontSize: 12, color: theme.color.accent },
  });
}
