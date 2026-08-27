import { Pressable, StyleSheet, Text } from 'react-native';

import { useTheme } from '@/theme/ThemeProvider';
import type { Theme } from '@/theme/theme';

type Props = {
  label: string;
  active: boolean;
  count?: number;
  onPress: () => void;
};

/** Tag is non-interactive and 10px; chips are touch targets — see mobile-components.md § New in this phase. */
export function FilterChip({ label, active, count, onPress }: Props) {
  const { theme } = useTheme();
  const styles = makeStyles(theme);

  return (
    <Pressable
      onPress={onPress}
      hitSlop={theme.hitSlop}
      android_ripple={null}
      accessibilityRole="button"
      accessibilityState={{ selected: active }}
      style={({ pressed }) => [styles.base, active && styles.active, pressed && !active && styles.pressed]}
    >
      <Text style={[styles.label, active && styles.labelActive]} numberOfLines={1}>
        {count != null ? `${label} · ${count}` : label}
      </Text>
    </Pressable>
  );
}

function makeStyles(theme: Theme) {
  return StyleSheet.create({
    base: {
      height: 32,
      paddingVertical: 6,
      paddingHorizontal: 12,
      borderWidth: 1,
      borderColor: theme.color.divider,
      alignItems: 'center',
      justifyContent: 'center',
    },
    active: { backgroundColor: theme.color.text, borderColor: theme.color.text },
    pressed: { backgroundColor: theme.color.surfaceAlt },
    label: { fontFamily: 'Archivo_600SemiBold', fontSize: 13, color: theme.color.text },
    labelActive: { color: theme.color.onInk, fontFamily: 'Archivo_800ExtraBold' },
  });
}
