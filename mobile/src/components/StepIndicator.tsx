import { StyleSheet, Text, View } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

type Props = {
  steps: string[];
  /** 0-indexed current step. */
  current: number;
};

/** The only progress that is navigational, not quantitative — see mobile-components.md § New in this phase. */
export function StepIndicator({ steps, current }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  return (
    <View style={styles.root}>
      <View style={styles.barRow}>
        {steps.map((_, i) => (
          <View key={i} style={[styles.bar, { backgroundColor: i <= current ? theme.color.text : theme.color.neutral300 }]} />
        ))}
      </View>
      <Text style={styles.caption}>
        Langkah {current + 1} dari {steps.length}
      </Text>
      <Text style={styles.title}>{steps[current]}</Text>
    </View>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    root: { paddingHorizontal: theme.layout.screenPadding, paddingTop: theme.space.md, gap: theme.space.xs },
    barRow: { flexDirection: 'row', gap: 4 },
    bar: { flex: 1, height: 2 },
    caption: { fontFamily: 'Archivo_600SemiBold', fontSize: 11, letterSpacing: 0.7, color: theme.color.textMuted, marginTop: theme.space.sm },
    title: { fontFamily: 'Archivo_800ExtraBold', fontSize: 17, color: theme.color.text },
  });
}
