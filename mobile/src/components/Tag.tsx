import { StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

export type TagTone = 'solid' | 'tint' | 'outline' | 'accent';

type Props = {
  tone: TagTone;
  label: string;
};

/** Always carries a visible text label — status is never color-only (grayscale test, section 09). */
export function Tag({ tone, label }: Props) {
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
    <View style={[styles.base, tone === 'outline' && styles.outlinePadding, toneStyle]}>
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
    outlineLabel: { color: theme.color.text },
    accent: { backgroundColor: theme.color.accent },
    accentLabel: { color: theme.color.onAccent },
  });
}
