import { StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

export type TagTone = 'solid' | 'tint' | 'outline' | 'accent';

type Props = {
  tone: TagTone;
  label: string;
  /**
   * Outline tag filled with `surfaceAlt` instead of transparent — the
   * "closed/inactive" treatment (mobile-design-system.md § Additions in
   * this phase): closed voting, returned inventory, archived documents,
   * cancelled events, exempt dues. Only meaningful with tone="outline".
   */
  mutedFill?: boolean;
};

/** Always carries a visible text label — status is never color-only (grayscale test, section 09). */
export function Tag({ tone, label, mutedFill }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  const toneStyle = {
    solid: styles.solid,
    tint: styles.tint,
    outline: styles.outline,
    accent: styles.accent,
  }[tone];

  const labelStyle = {
    solid: styles.solidLabel,
    tint: styles.tintLabel,
    outline: styles.outlineLabel,
    accent: styles.accentLabel,
  }[tone];

  return (
    <View
      style={[
        styles.base,
        tone === 'outline' && styles.outlinePadding,
        toneStyle,
        tone === 'outline' && mutedFill && styles.outlineMuted,
      ]}
    >
      <Text style={[styles.label, labelStyle]} numberOfLines={1}>
        {label}
      </Text>
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    base: {
      alignSelf: 'flex-start',
      borderRadius: theme.radius,
      paddingVertical: 4,
      paddingHorizontal: 8,
    },
    outlinePadding: { paddingVertical: 3 },
    label: {
      fontFamily: 'Archivo_800ExtraBold',
      fontSize: 10.5,
      letterSpacing: 0.5,
      textTransform: 'uppercase',
    },
    solid: { backgroundColor: theme.color.text },
    solidLabel: { color: theme.color.onInk },
    tint: { backgroundColor: theme.color.accent200 },
    tintLabel: { color: theme.color.accent800 },
    outline: { backgroundColor: 'transparent', borderWidth: 1, borderColor: theme.color.divider },
    outlineMuted: { backgroundColor: theme.color.surfaceAlt },
    outlineLabel: { color: theme.color.text },
    accent: { backgroundColor: theme.color.accent },
    accentLabel: { color: theme.color.onAccent },
  });
}
