import { StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

type Props = {
  variant: 'segmented' | 'bar';
  value: number;
  total: number;
  /** Progress is never shown without its number — "3 dari 4", "18 dari 24", etc. */
  label: string;
  /** 'accent' for dues, 'text' for tasks/prep. Ignored for segmented (always text-filled). */
  tone?: 'accent' | 'text';
  /** Height 6 instead of the default 10 — used inline in compact cards (e.g. EventItem). */
  compact?: boolean;
  /** False when the caller already renders the same number elsewhere (e.g. Kas's dues caption row) — `label` still becomes the accessibilityLabel either way. */
  showLabel?: boolean;
};

export function Progress({ variant, value, total, label, tone = 'text', compact, showLabel = true }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);
  const height = compact ? 6 : 10;
  const fillColor = tone === 'accent' ? theme.color.accent : theme.color.text;

  return (
    <View
      accessibilityRole="progressbar"
      accessibilityValue={{ min: 0, max: total, now: value }}
      accessibilityLabel={label}
    >
      {variant === 'segmented' ? (
        <View style={[styles.segmentedRow, { height }]}>
          {Array.from({ length: total }).map((_, i) => (
            <View
              key={i}
              style={[
                styles.segment,
                { height, backgroundColor: i < value ? theme.color.text : theme.color.neutral300 },
              ]}
            />
          ))}
        </View>
      ) : (
        <View style={[styles.bar, { height, backgroundColor: theme.color.neutral300 }]}>
          <View
            style={[
              styles.fill,
              { height, backgroundColor: fillColor, width: `${total > 0 ? Math.min(100, (value / total) * 100) : 0}%` },
            ]}
          />
        </View>
      )}
      {showLabel ? <Text style={styles.label}>{label}</Text> : null}
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    segmentedRow: { flexDirection: 'row', gap: 2 },
    segment: { flex: 1, borderRadius: theme.radius },
    bar: { width: '100%', borderRadius: theme.radius, overflow: 'hidden' },
    fill: { borderRadius: theme.radius },
    label: { fontFamily: 'Archivo_600SemiBold', fontSize: 11, letterSpacing: 0.7, color: theme.color.textMuted, marginTop: 4 },
  });
}
